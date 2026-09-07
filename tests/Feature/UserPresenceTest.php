<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserPresenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    private function member(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'approved',
            'email_verified_at' => now(),
        ], $overrides));
    }

    public function test_an_authenticated_request_records_when_the_member_was_seen(): void
    {
        $member = $this->member();
        $this->assertNull($member->last_seen_at);

        $this->actingAs($member)->get('/dashboard');

        $this->assertNotNull($member->fresh()->last_seen_at);
    }

    public function test_the_write_is_throttled_rather_than_once_per_request(): void
    {
        $member = $this->member();

        $this->actingAs($member)->get('/dashboard');
        $first = $member->fresh()->last_seen_at;

        $this->travel(10)->seconds();
        $this->actingAs($member)->get('/dashboard');

        $this->assertTrue(
            $first->equalTo($member->fresh()->last_seen_at),
            'A second request inside the throttle window must not write again.'
        );

        // Past the window, it moves.
        $this->travel((int) config('presence.write_every_seconds') + 5)->seconds();
        $this->actingAs($member)->get('/dashboard');

        $this->assertTrue($member->fresh()->last_seen_at->gt($first));
    }

    public function test_recording_presence_does_not_disturb_the_account_record(): void
    {
        // Reading a page is not a change to your account: it must not move
        // updated_at, or every member looks freshly edited in the admin list.
        $member = $this->member();
        $member->forceFill(['updated_at' => now()->subWeek()])->saveQuietly();
        $before = $member->fresh()->updated_at;

        $this->actingAs($member)->get('/dashboard');

        $this->assertTrue($before->equalTo($member->fresh()->updated_at));
    }

    public function test_a_signed_out_visitor_records_nothing(): void
    {
        $member = $this->member();

        $this->get('/');

        $this->assertNull($member->fresh()->last_seen_at);
    }

    public function test_presence_reads_online_away_offline_and_never(): void
    {
        $this->assertSame('never', $this->member(['last_seen_at' => null])->presence());
        $this->assertSame('online', $this->member(['last_seen_at' => now()->subMinute()])->presence());
        $this->assertSame('away', $this->member(['last_seen_at' => now()->subMinutes(20)])->presence());
        $this->assertSame('offline', $this->member(['last_seen_at' => now()->subHours(5)])->presence());
    }

    public function test_the_online_window_is_configurable(): void
    {
        $member = $this->member(['last_seen_at' => now()->subMinutes(8)]);

        $this->assertFalse($member->isOnline());

        config(['presence.online_minutes' => 15]);

        $this->assertTrue($member->fresh()->isOnline());
    }

    public function test_the_admin_list_can_be_filtered_by_activity(): void
    {
        $online = $this->member(['last_seen_at' => now()]);
        $dormant = $this->member(['last_seen_at' => now()->subDays(45)]);
        $never = $this->member(['last_seen_at' => null]);

        $this->assertTrue(User::online()->pluck('id')->contains($online->id));
        $this->assertFalse(User::online()->pluck('id')->contains($dormant->id));
        $this->assertFalse(User::online()->pluck('id')->contains($never->id));

        $this->assertTrue(User::seenSince(now()->subDays(60))->pluck('id')->contains($dormant->id));
    }

    public function test_the_user_table_renders_with_the_new_columns(): void
    {
        $admin = $this->member();
        $admin->assignRole('super_admin');
        $this->member(['last_seen_at' => now()]);
        $this->member(['last_seen_at' => null]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();

        $columns = array_keys(UserResource::table(new \Filament\Tables\Table(new \Filament\Resources\Pages\ListRecords))->getColumns());
        $this->assertContains('last_seen_at', $columns);
        $this->assertContains('presence', $columns);
    }
}
