<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Due;
use App\Models\Payment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function payDue(Request $request, Due $due)
    {
        $request->validate(['payment_method' => 'required|in:paystack,stripe']);

        $result = $this->paymentService->initiatePayment(
            $due,
            $request->payment_method,
            $due->amount,
            $due->currency,
            $request->user()->email,
        );

        if (isset($result['authorization_url'])) {
            return redirect($result['authorization_url']);
        }
        if (isset($result['url'])) {
            return redirect($result['url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function payCampaign(Request $request, Campaign $campaign)
    {
        $request->validate([
            'amount' => 'required|integer|min:100',
            'payment_method' => 'required|in:paystack,stripe',
        ]);

        $result = $this->paymentService->initiatePayment(
            $campaign,
            $request->payment_method,
            $request->amount,
            $campaign->currency,
            $request->user()->email,
        );

        if (isset($result['authorization_url'])) {
            return redirect($result['authorization_url']);
        }
        if (isset($result['url'])) {
            return redirect($result['url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function success(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('session_id');

        if ($request->query('reference')) {
            // Paystack: verify with the gateway before marking successful.
            $payment = $this->paymentService->verifyPaystackPayment($reference);
        } else {
            // Stripe: the redirect is informational only. The webhook (POST /api/webhooks/stripe)
            // is the authoritative confirmation path. Show the current payment state; the UI
            // should poll or instruct the user to wait for the email confirmation.
            $payment = Payment::where('payment_reference', $reference)->first();
        }

        return Inertia::render('Payments/Success', [
            'payment' => $payment?->load('payable'),
        ]);
    }

    public function cancel(Request $request)
    {
        $reference = $request->query('reference');
        if ($reference) {
            Payment::where('payment_reference', $reference)->update(['status' => 'failed']);
        }

        return redirect()->route('dashboard')->with('error', 'Payment was cancelled.');
    }

    public function verifyPaystack(Request $request)
    {
        $reference = $request->input('reference');
        $payment = $this->paymentService->verifyPaystackPayment($reference);

        return response()->json([
            'status' => $payment?->status,
            'payment_id' => $payment?->id,
        ]);
    }

    public function history(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with('payable')
            ->latest()
            ->paginate(20);

        return Inertia::render('Payments/History', [
            'payments' => $payments,
        ]);
    }

    public function receipt(Payment $payment)
    {
        if ($payment->user_id !== auth()->id() && !auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        $payment->load(['payable', 'user']);

        $pdf = Pdf::loadView('receipts.payment', [
            'payment' => $payment,
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
        ]);

        return $pdf->download("receipt-{$payment->payment_reference}.pdf");
    }

    public function stripeWebhook(Request $request)
    {
        $payload = $request->all();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $this->paymentService->handleStripeWebhook($event->toArray());

        return response()->json(['status' => 'ok']);
    }
}
