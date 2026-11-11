<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public const METHODS = ['paystack', 'stripe'];

    public function __construct(
        protected PaystackService $paystack,
        protected StripeService $stripe,
    ) {}

    /**
     * Gateways that can actually take money right now: a gateway with no secret
     * key configured is hidden from members and refused by the server, so the
     * site can run with online payment switched off until the keys go in.
     */
    public function availableMethods(): array
    {
        return array_values(array_filter(self::METHODS, fn (string $method) => match ($method) {
            'paystack' => filled(config('services.paystack.secret')),
            'stripe' => filled(config('services.stripe.secret')),
        }));
    }

    public function generateReference(): string
    {
        return strtoupper('UNI-' . Str::random(8) . '-' . now()->timestamp);
    }

    /**
     * Opens a pending payment and asks the gateway for a checkout page.
     * Returns ['redirect' => url] on success or ['error' => message] for the member.
     */
    public function initiatePayment(mixed $payable, string $method, int $amount, string $currency, string $email): array
    {
        if (! in_array($method, $this->availableMethods(), true)) {
            return ['error' => 'That payment method is not available yet.'];
        }

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

        $description = $this->describe($payable);

        try {
            $url = $method === 'paystack'
                ? $this->paystack->initializeTransaction($email, $amount, $reference, [
                    'currency' => $currency,
                    'payment_id' => $payment->id,
                    'callback_url' => route('payments.success', ['gateway' => 'paystack']),
                ])['data']['authorization_url'] ?? null
                : $this->stripe->createCheckoutSession(
                    $email,
                    $amount,
                    $currency,
                    route('payments.success', ['gateway' => 'stripe', 'reference' => $reference]),
                    route('payments.cancel', ['reference' => $reference]),
                    ['payment_id' => $payment->id, 'description' => $description],
                )['url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('Payment initialisation failed', ['payment_id' => $payment->id, 'method' => $method, 'error' => $e->getMessage()]);
            $url = null;
        }

        if (! $url) {
            // Never leave an orphan "pending" row for a checkout that never opened.
            $payment->update(['status' => 'failed']);

            return ['error' => 'We could not reach the payment provider. Please try again shortly.'];
        }

        return ['redirect' => $url, 'reference' => $reference, 'payment_id' => $payment->id];
    }

    /**
     * Asks Paystack for the truth about a reference. Used by the browser
     * redirect and by the webhook, so whichever arrives first confirms it.
     */
    public function verifyPaystackPayment(?string $reference): ?Payment
    {
        $payment = $reference ? Payment::where('payment_reference', $reference)->where('payment_method', 'paystack')->first() : null;
        if (! $payment || $payment->status === 'successful') {
            return $payment;
        }

        try {
            $result = $this->paystack->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::warning('Paystack verification failed', ['reference' => $reference, 'error' => $e->getMessage()]);

            return $payment;
        }

        $data = $result['data'] ?? [];
        $status = $data['status'] ?? null;

        if ($status === 'success') {
            if ((int) ($data['amount'] ?? -1) !== $payment->amount || strcasecmp($data['currency'] ?? '', $payment->currency) !== 0) {
                Log::error('Paystack amount/currency mismatch', ['payment_id' => $payment->id, 'gateway' => $data]);

                return $payment;
            }

            return $this->markSuccessful($payment, $data);
        }

        // "abandoned"/"ongoing" mean the member may still finish; only a real failure closes it.
        if ($status === 'failed') {
            $this->markFailed($payment, $data);
        }

        return $payment->fresh();
    }

    public function handlePaystackWebhook(array $payload): ?Payment
    {
        if (($payload['event'] ?? null) !== 'charge.success') {
            return null;
        }

        // Don't trust the body's claim; re-verify against the API.
        return $this->verifyPaystackPayment($payload['data']['reference'] ?? null);
    }

    public function handleStripeWebhook(array $payload): ?Payment
    {
        $type = $payload['type'] ?? null;
        $session = $payload['data']['object'] ?? [];
        $paymentId = $session['metadata']['payment_id'] ?? null;
        $payment = $paymentId ? Payment::where('payment_method', 'stripe')->find($paymentId) : null;

        if (! $payment) {
            return null;
        }

        $paid = ($type === 'checkout.session.completed' && ($session['payment_status'] ?? null) === 'paid')
            || $type === 'checkout.session.async_payment_succeeded';

        if ($paid) {
            if ((int) ($session['amount_total'] ?? -1) !== $payment->amount || strcasecmp($session['currency'] ?? '', $payment->currency) !== 0) {
                Log::error('Stripe amount/currency mismatch', ['payment_id' => $payment->id]);

                return $payment;
            }

            return $this->markSuccessful($payment, $session);
        }

        if (in_array($type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
            $this->markFailed($payment, $session);
        }

        return $payment->fresh();
    }

    /**
     * The one place a payment becomes successful. Locks the row so a webhook and
     * a browser redirect racing each other apply the side effects exactly once.
     */
    public function markSuccessful(Payment $payment, array $gatewayResponse = []): Payment
    {
        return DB::transaction(function () use ($payment, $gatewayResponse) {
            $locked = Payment::lockForUpdate()->find($payment->id);
            if ($locked->status === 'successful') {
                return $locked;
            }

            $locked->update([
                'status' => 'successful',
                'paid_at' => now(),
                'gateway_response' => $gatewayResponse ?: $locked->gateway_response,
            ]);

            $this->applyPostPaymentEffects($locked);

            return $locked;
        });
    }

    protected function markFailed(Payment $payment, array $gatewayResponse = []): void
    {
        Payment::whereKey($payment->id)->where('status', 'pending')
            ->update(['status' => 'failed', 'gateway_response' => json_encode($gatewayResponse)]);
    }

    protected function applyPostPaymentEffects(Payment $payment): void
    {
        $payable = $payment->payable;

        if ($payable instanceof Campaign) {
            $payable->update(['raised_amount' => Payment::where('payable_type', Campaign::class)
                ->where('payable_id', $payable->id)
                ->where('status', 'successful')
                ->sum('amount')]);
        }

        // A successful event-ticket payment is what actually grants the ticket.
        if ($payable instanceof Event) {
            EventRsvp::updateOrCreate(
                ['event_id' => $payable->id, 'user_id' => $payment->user_id],
                ['status' => 'going', 'paid' => true, 'payment_reference' => $payment->payment_reference],
            );
        }
    }

    protected function describe(mixed $payable): string
    {
        $name = $payable->name ?? $payable->title ?? class_basename($payable);

        return config('app.name') . ': ' . $name;
    }
}
