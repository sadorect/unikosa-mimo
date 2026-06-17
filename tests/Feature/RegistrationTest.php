<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_new_registrations_enter_pending_status_and_get_member_role(): void
    {
        $action = new \App\Actions\Fortify\CreateNewUser();

        $user = $action->create([
            'name' => 'Ada Eze',
            'email' => 'ada@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertSame('pending', $user->status);
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        (new \App\Actions\Fortify\CreateNewUser())->create([
            'name' => 'Dup',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    }
}
