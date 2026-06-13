<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret');
    }

    public function initializeTransaction(string $email, int $amount, string $reference, array $metadata = []): array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email' => $email,
                'amount' => $amount,
                'reference' => $reference,
                'currency' => $metadata['currency'] ?? 'NGN',
                'metadata' => array_merge($metadata, [
                    'custom_fields' => [
                        [
                            'display_name' => 'Platform',
                            'variable_name' => 'platform',
                            'value' => config('app.name'),
                        ],
                    ],
                ]),
            ]);

        return $response->json();
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        return $response->json();
    }

    public function createPlan(string $name, int $amount, string $interval, int $duration = 0): array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/plan", [
                'name' => $name,
                'amount' => $amount,
                'interval' => $interval,
                'duration' => $duration,
            ]);

        return $response->json();
    }

    public function createSubscription(string $email, int $planCode, array $metadata = []): array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/subscription", [
                'customer' => $email,
                'plan' => $planCode,
                'start_date' => now()->addDay()->format('Y-m-d'),
            ]);

        return $response->json();
    }
}
