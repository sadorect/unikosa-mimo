<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Customer;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(string $email, int $amount, string $currency, string $successUrl, string $cancelUrl, array $metadata = []): array
    {
        $session = CheckoutSession::create([
            'customer_email' => $email,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => ['name' => $metadata['description'] ?? config('app.name') . ' Payment'],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);

        return ['url' => $session->url, 'session_id' => $session->id];
    }

    public function retrieveSession(string $sessionId): CheckoutSession
    {
        return CheckoutSession::retrieve($sessionId);
    }

    public function createPaymentIntent(int $amount, string $currency, array $metadata = []): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => $metadata,
        ]);
    }
}
