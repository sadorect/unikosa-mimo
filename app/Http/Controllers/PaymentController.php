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
            Payment::where('payment_reference', $reference)
                ->where('user_id', $request->user()->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);
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

    public function dues(Request $request)
    {
        $user = $request->user();

        $paidDueIds = Payment::where('user_id', $user->id)
            ->where('payable_type', Due::class)
            ->where('status', 'successful')
            ->pluck('payable_id');

        $dues = Due::where(function ($query) use ($user) {
            $query->where('set_id', $user->graduating_set_id)
                ->orWhere('chapter_id', $user->chapter_id)
                ->orWhere(function ($query) {
                    $query->whereNull('set_id')->whereNull('chapter_id');
                });
        })
            ->whereNotIn('id', $paidDueIds)
            ->latest()
            ->get();

        return Inertia::render('Payments/Dues', [
            'dues' => $dues,
        ]);
    }

    public function showDue(Due $due)
    {
        return Inertia::render('Payments/PayDue', [
            'due' => $due,
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
        if ($payment->user_id !== auth()->id() && !auth()->user()->can('manage payments')) {
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
