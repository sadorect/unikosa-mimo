<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function member(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'email_verified_at' => now(),
        ]);
    }

    public function test_disabled_module_returns_503(): void
    {
        Setting::set('blog_enabled', 'false', 'modules');

        $this->actingAs($this->member())
            ->get('/blog')
            ->assertStatus(503);
    }

    public function test_enabled_module_is_reachable(): void
    {
        Setting::set('blog_enabled', 'true', 'modules');

        $this->actingAs($this->member())
            ->get('/blog')
            ->assertSuccessful();
    }
}
