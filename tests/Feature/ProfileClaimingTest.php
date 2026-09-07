<?php

namespace Tests\Feature;

use App\Mail\ClaimProfileOtp;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProfileClaimingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function importedUser(): User
    {
        return User::factory()->create([
            'email' => 'imported@example.com',
            'name' => 'Imported Member',
            'imported' => true,
            'account_claimed' => false,
            'password' => null,
            'status' => 'approved',
        ]);
    }

    public function test_search_finds_imported_unclaimed_profiles(): void
    {
        $this->importedUser();

        $this->postJson('/api/v1/claim-profile/search', ['query' => 'imported@example.com'])
            ->assertOk()
            ->assertJsonPath('results.0.email', 'imported@example.com');
    }

    public function test_send_otp_emails_the_member(): void
    {
        Mail::fake();
        $user = $this->importedUser();

        $this->postJson('/api/v1/claim-profile/send-otp', ['user_id' => $user->id])
            ->assertOk();

        Mail::assertSent(ClaimProfileOtp::class);
        $this->assertStringStartsWith('claim_', $user->fresh()->remember_token);
    }

    public function test_member_claims_profile_with_valid_otp(): void
    {
        $user = $this->importedUser();
        $user->forceFill(['remember_token' => 'claim_ABC123_' . now()->timestamp])->save();

        $this->post('/claim-profile/verify', [
            'user_id' => $user->id,
            'otp' => 'ABC123',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue($user->account_claimed);
        $this->assertNotNull($user->claimed_at);
        $this->assertNull($user->remember_token);
    }

    public function test_invalid_otp_is_rejected(): void
    {
        $user = $this->importedUser();
        $user->forceFill(['remember_token' => 'claim_ABC123_' . now()->timestamp])->save();

        $this->postJson('/claim-profile/verify', [
            'user_id' => $user->id,
            'otp' => 'WRONG1',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertStatus(422);

        $this->assertFalse($user->fresh()->account_claimed);
    }
}
