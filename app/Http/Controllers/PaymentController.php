<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Due;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Validation\Rule;
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
        $user = $request->user();
        abort_unless($this->duesFor($user)->whereKey($due->id)->exists(), 404);

        if ($this->hasPaid($user->id, $due)) {
            return redirect()->route('payments.dues.index')->with('error', 'You have already paid this due.');
        }

        $request->validate(['payment_method' => ['required', Rule::in($this->paymentService->availableMethods())]], [
            'payment_method.in' => 'Online payment is not available yet.',
        ]);

        return $this->toCheckout($this->paymentService->initiatePayment(
            $due, $request->payment_method, $due->amount, $due->currency, $user->email,
        ));
    }

    public function payCampaign(Request $request, Campaign $campaign)
    {
        abort_unless($campaign->is_active, 404);

        $request->validate([
            'amount' => 'required|integer|min:100',
            'payment_method' => ['required', Rule::in($this->paymentService->availableMethods())],
        ], ['payment_method.in' => 'Online payment is not available yet.']);

        return $this->toCheckout($this->paymentService->initiatePayment(
            $campaign, $request->payment_method, (int) $request->amount, $campaign->currency, $request->user()->email,
        ));
    }

    /** Where both gateways send the member back after checkout. */
    public function success(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        $payment = $reference
            ? Payment::where('payment_reference', $reference)->where('user_id', $request->user()->id)->first()
            : null;

        // Paystack: confirm with the gateway now. Stripe: the signed webhook is the
        // authority, so this page just shows whatever state it has reached.
        if ($payment && $payment->payment_method === 'paystack') {
            $payment = $this->paymentService->verifyPaystackPayment($reference);
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

        return redirect()->route('payments.history')->with('error', 'Payment was cancelled.');
    }

    public function verifyPaystack(Request $request)
    {
        $reference = (string) $request->input('reference');
        abort_unless(Payment::where('payment_reference', $reference)->where('user_id', $request->user()->id)->exists(), 404);

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

        $dues = $this->duesFor($user)
            ->whereNotIn('id', $paidDueIds)
            ->latest()
            ->get();

        return Inertia::render('Payments/Dues', [
            'dues' => $dues,
        ]);
    }

    public function showDue(Request $request, Due $due)
    {
        abort_unless($this->duesFor($request->user())->whereKey($due->id)->exists(), 404);

        if ($this->hasPaid($request->user()->id, $due)) {
            return redirect()->route('payments.dues.index')->with('success', 'You have already paid this due.');
        }

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
        abort_unless($payment->status === 'successful', 404, 'A receipt is only available once the payment has gone through.');

        $payment->load(['payable', 'user']);

        $pdf = Pdf::loadView('receipts.payment', [
            'payment' => $payment,
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
        ]);

        return $pdf->download("receipt-{$payment->payment_reference}.pdf");
    }

    public function paystackWebhook(Request $request, PaystackService $paystack)
    {
        if (! $paystack->validWebhookSignature($request->getContent(), $request->header('X-Paystack-Signature'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $this->paymentService->handlePaystackWebhook($request->json()->all());

        return response()->json(['status' => 'ok']);
    }

    public function stripeWebhook(Request $request)
    {
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $this->paymentService->handleStripeWebhook($event->toArray());

        return response()->json(['status' => 'ok']);
    }

    protected function toCheckout(array $result)
    {
        return isset($result['redirect'])
            ? Inertia::location($result['redirect'])
            : back()->with('error', $result['error'] ?? 'Payment initialization failed.');
    }

    protected function duesFor($user)
    {
        return Due::applicableTo($user);
    }

    protected function hasPaid(int $userId, Due $due): bool
    {
        return Payment::where('user_id', $userId)
            ->where('payable_type', Due::class)
            ->where('payable_id', $due->id)
            ->where('status', 'successful')
            ->exists();
    }
}
