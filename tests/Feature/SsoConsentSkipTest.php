<?php

namespace Tests\Feature;

use App\Models\Passport\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SsoConsentSkipTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_party_client_skips_the_oauth_consent_screen(): void
    {
        Passport::loadKeysFrom(storage_path());

        // A first-party chapter-site client (no owner) — like Unikosana NA.
        $client = Client::create([
            'name' => 'Test Chapter Site',
            'secret' => 'test-secret',
            'redirect_uris' => ['https://chapter.example.com/auth/national/callback'],
            'grant_types' => ['authorization_code'],
            'revoked' => false,
        ]);

        $user = User::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($user)->get('/oauth/authorize?' . http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://chapter.example.com/auth/national/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => 'teststate123',
        ]));

        // Must redirect straight back to the callback with an auth code —
        // never render the consent form (which is what the sandboxed iframe
        // was blocking).
        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringStartsWith('https://chapter.example.com/auth/national/callback', $location);
        $this->assertStringContainsString('code=', $location);
        $this->assertStringContainsString('state=teststate123', $location);
    }
}
