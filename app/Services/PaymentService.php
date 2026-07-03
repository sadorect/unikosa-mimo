<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentService
{
    protected PaystackService $paystack;
    protected StripeService $stripe;

    public function __construct(PaystackService $paystack, StripeService $stripe)
    {
        $this->paystack = $paystack;
        $this->stripe = $stripe;
    }

    public function generateReference(): string
    {
        return strtoupper('UNI-' . Str::random(8) . '-' . now()->timestamp);
    }

    public function initiatePayment(mixed $payable, string $method, int $amount, string $currency, string $email): array
    {
        $reference = $this->generateReference();

        $payment = Payment::create([
            'user_id' => auth()->id(),
            'payable_type' => get_class($payable),
            'payable_id' => $payable->id,
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $method,
            'payment_reference' => $reference,
            'status' => 'pending',
        ]);

        if ($method === 'paystack') {
            $result = $this->paystack->initializeTransaction($email, $amount, $reference, [
                'currency' => $currency,
                'payment_id' => $payment->id,
            ]);

            return [
                'authorization_url' => $result['data']['authorization_url'] ?? null,
                'reference' => $reference,
                'payment_id' => $payment->id,
            ];
        }

        if ($method === 'stripe') {
            $result = $this->stripe->createCheckoutSession(
                $email,
                $amount,
                $currency,
                route('payments.success', ['reference' => $reference]),
                route('payments.cancel', ['reference' => $reference]),
                ['payment_id' => $payment->id]
            );

            return [
                'url' => $result['url'],
                'reference' => $reference,
                'payment_id' => $payment->id,
            ];
        }

        return ['error' => 'Invalid payment method'];
    }

    public function verifyPaystackPayment(string $reference): ?Payment
    {
        $result = $this->paystack->verifyTransaction($reference);

        $payment = Payment::where('payment_reference', $reference)->first();
        if (!$payment) return null;

        if ($result['data']['status'] === 'success') {
            $payment->update([
                'status' => 'successful',
                'paid_at' => now(),
                'gateway_response' => $result['data'],
            ]);

            $this->applyPostPaymentEffects($payment);
        } else {
            $payment->update(['status' => 'failed']);
        }

        return $payment;
    }

    public function handleStripeWebhook(array $payload): ?Payment
    {
        if ($payload['type'] !== 'checkout.session.completed') return null;

        $session = $payload['data']['object'];
        $paymentId = $session['metadata']['payment_id'] ?? null;

        $payment = $paymentId ? Payment::find($paymentId) : Payment::where('payment_reference', $session['payment_intent'])->first();
        if (!$payment) return null;

        // Idempotency guard: skip if already confirmed (webhook may fire more than once).
        if ($payment->status === 'successful') return $payment;

        $payment->update([
            'status' => 'successful',
            'paid_at' => now(),
            'gateway_response' => $session,
        ]);

        $this->updatePayableAmount($payment);

        return $payment;
    }

    /**
     * Side effects that must fire exactly once when a payment becomes successful.
     * Both success paths (Paystack verify, Stripe webhook) are idempotent before
     * calling this, so this runs once per payment.
     */
    protected function applyPostPaymentEffects(Payment $payment): void
    {
        $payable = $payment->payable;

        // Campaigns/other fundraising payables track a running total.
        if ($payable && method_exists($payable, 'raised_amount')) {
            $totalPaid = Payment::where('payable_type', $payment->payable_type)
                ->where('payable_id', $payment->payable_id)
                ->where('status', 'successful')
                ->sum('amount');

            $payable->update(['raised_amount' => $totalPaid]);
        }

        // A successful event-ticket payment is what actually grants the buyer a ticket:
        // record it on their RSVP so they (and organizers) have a trackable, paid attendance.
        if ($payable instanceof Event) {
            EventRsvp::updateOrCreate(
                ['event_id' => $payable->id, 'user_id' => $payment->user_id],
                ['status' => 'going', 'paid' => true, 'payment_reference' => $payment->payment_reference],
            );
        }
    }
}
