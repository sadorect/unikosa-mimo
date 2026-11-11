<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Due;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Due $due;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.paystack.secret' => 'sk_test_paystack',
            'services.stripe.secret' => 'sk_test_stripe',
            'services.stripe.webhook_secret' => 'whsec_test',
        ]);
        $this->member = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $this->due = Due::create(['name' => 'Annual Dues', 'amount' => 500000, 'currency' => 'NGN', 'frequency' => 'annual']);
    }

    private function fakePaystack(string $status = 'success', int $amount = 500000, string $currency = 'NGN'): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc']]),
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => $status, 'amount' => $amount, 'currency' => $currency]]),
        ]);
    }

    private function startPaystackDue(): Payment
    {
        $this->actingAs($this->member)
            ->post(route('payments.due', $this->due), ['payment_method' => 'paystack'])
            ->assertRedirect('https://checkout.paystack.com/abc');

        return Payment::latest('id')->first();
    }

    public function test_without_keys_no_method_is_offered_and_none_is_accepted(): void
    {
        config(['services.paystack.secret' => '', 'services.stripe.secret' => null]);
        Http::fake();

        $this->assertSame([], app(PaymentService::class)->availableMethods());

        $this->actingAs($this->member)
            ->get(route('payments.dues.show', $this->due))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('settings.payment_methods', []));

        $this->actingAs($this->member)
            ->post(route('payments.due', $this->due), ['payment_method' => 'paystack'])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Payment::count());
        Http::assertNothingSent();
    }

    public function test_only_configured_gateways_are_offered(): void
    {
        config(['services.stripe.secret' => '']);

        $this->assertSame(['paystack'], app(PaymentService::class)->availableMethods());
    }

    public function test_a_paystack_due_payment_completes_on_return_and_sends_a_callback_url(): void
    {
        $this->fakePaystack();
        $payment = $this->startPaystackDue();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'initialize') && str_contains($r['callback_url'], '/payments/success'));
        $this->assertSame('pending', $payment->status);

        $this->actingAs($this->member)
            ->get(route('payments.success', ['reference' => $payment->payment_reference]))
            ->assertOk();

        $this->assertSame('successful', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);

        // Paid dues disappear from the list and cannot be paid twice.
        $this->actingAs($this->member)->get(route('payments.dues.index'))
            ->assertInertia(fn ($page) => $page->has('dues', 0));
        $this->actingAs($this->member)
            ->post(route('payments.due', $this->due), ['payment_method' => 'paystack'])
            ->assertRedirect(route('payments.dues.index'));
    }

    public function test_a_paystack_amount_mismatch_is_not_accepted(): void
    {
        $this->fakePaystack('success', 100);
        $payment = $this->startPaystackDue();

        app(PaymentService::class)->verifyPaystackPayment($payment->payment_reference);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_an_abandoned_paystack_checkout_stays_pending(): void
    {
        $this->fakePaystack('abandoned');
        $payment = $this->startPaystackDue();

        app(PaymentService::class)->verifyPaystackPayment($payment->payment_reference);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_the_paystack_webhook_needs_a_valid_signature_and_confirms_the_payment(): void
    {
        $this->fakePaystack();
        $payment = $this->startPaystackDue();
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->payment_reference]]);

        $this->call('POST', '/api/webhooks/paystack', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'forged'], $body)
            ->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);

        $this->call('POST', '/api/webhooks/paystack', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_paystack'),
        ], $body)->assertOk();

        $this->assertSame('successful', $payment->fresh()->status);
    }

    public function test_a_gateway_outage_fails_the_payment_and_tells_the_member(): void
    {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false, 'message' => 'Invalid key'], 401)]);

        $this->actingAs($this->member)
            ->from(route('payments.dues.show', $this->due))
            ->post(route('payments.due', $this->due), ['payment_method' => 'paystack'])
            ->assertRedirect(route('payments.dues.show', $this->due))
            ->assertSessionHas('error');

        $this->assertSame('failed', Payment::first()->status);
    }

    private function stripeWebhook(array $event)
    {
        $body = json_encode($event);
        $t = time();
        $sig = hash_hmac('sha256', "{$t}.{$body}", 'whsec_test');

        return $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}",
        ], $body);
    }

    public function test_a_stripe_campaign_donation_is_confirmed_by_webhook_once_and_updates_the_total(): void
    {
        $campaign = Campaign::create([
            'title' => 'Hall Rebuild', 'slug' => 'hall', 'description' => 'x', 'target_amount' => 1000000,
            'currency' => 'NGN', 'created_by' => $this->member->id,
        ]);
        $payment = Payment::create([
            'user_id' => $this->member->id, 'payable_type' => Campaign::class, 'payable_id' => $campaign->id,
            'amount' => 250000, 'currency' => 'NGN', 'payment_method' => 'stripe',
            'payment_reference' => 'UNI-TEST', 'status' => 'pending',
        ]);

        $event = ['id' => 'evt_1', 'object' => 'event', 'type' => 'checkout.session.completed', 'data' => ['object' => [
            'object' => 'checkout.session', 'payment_status' => 'paid', 'amount_total' => 250000, 'currency' => 'ngn',
            'metadata' => ['payment_id' => (string) $payment->id],
        ]]];

        $this->stripeWebhook($event)->assertOk();
        $this->stripeWebhook($event)->assertOk(); // Stripe retries; must not double count.

        $this->assertSame('successful', $payment->fresh()->status);
        $this->assertSame(250000, $campaign->fresh()->raised_amount);

        // The Stripe return page shows state; it must not be treated as a Paystack reference.
        $this->actingAs($this->member)
            ->get(route('payments.success', ['gateway' => 'stripe', 'reference' => 'UNI-TEST']))
            ->assertInertia(fn ($page) => $page->where('payment.status', 'successful'));
    }

    public function test_an_unsigned_stripe_webhook_is_rejected(): void
    {
        $this->postJson('/api/webhooks/stripe', ['type' => 'checkout.session.completed'])->assertStatus(400);
    }

    public function test_receipts_exist_only_for_successful_payments_and_their_owner(): void
    {
        $payment = Payment::create([
            'user_id' => $this->member->id, 'payable_type' => Due::class, 'payable_id' => $this->due->id,
            'amount' => 500000, 'currency' => 'NGN', 'payment_method' => 'paystack',
            'payment_reference' => 'UNI-R', 'status' => 'pending',
        ]);

        $this->actingAs($this->member)->get(route('payments.receipt', $payment))->assertNotFound();

        $other = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
        $payment->update(['status' => 'successful', 'paid_at' => now()]);
        $this->actingAs($other)->get(route('payments.receipt', $payment))->assertForbidden();
        $this->actingAs($this->member)->get(route('payments.receipt', $payment))->assertOk();
    }

    public function test_a_due_for_another_set_cannot_be_paid(): void
    {
        $this->fakePaystack();
        $set = \App\Models\Set::factory()->create();
        $otherDue = Due::create(['name' => 'Levy', 'amount' => 1000, 'currency' => 'NGN', 'frequency' => 'one_time', 'set_id' => $set->id]);

        $this->actingAs($this->member)
            ->post(route('payments.due', $otherDue), ['payment_method' => 'paystack'])
            ->assertNotFound();
    }

    public function test_another_sets_levy_does_not_count_against_a_member_without_a_chapter(): void
    {
        $set = \App\Models\Set::factory()->create();
        Due::create(['name' => 'Levy', 'amount' => 1000, 'currency' => 'NGN', 'frequency' => 'one_time', 'set_id' => $set->id]);

        $this->assertSame([$this->due->id], Due::applicableTo($this->member)->pluck('id')->all());

        Payment::create([
            'user_id' => $this->member->id, 'payable_type' => Due::class, 'payable_id' => $this->due->id,
            'amount' => 500000, 'currency' => 'NGN', 'payment_method' => 'paystack',
            'payment_reference' => 'UNI-E', 'status' => 'successful', 'paid_at' => now(),
        ]);
        $this->assertTrue((new \App\Support\Eligibility\Rules\DuesCurrentRule)->passes($this->member, []));
    }
}
