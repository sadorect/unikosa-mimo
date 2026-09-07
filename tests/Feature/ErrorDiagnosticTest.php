<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ErrorDiagnostic;
use BadMethodCallException;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * A 500 has to tell the person who runs the platform what actually broke.
 * Everybody else still gets the plain apology.
 */
class ErrorDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['app.force_error_pages' => true]);

        Route::middleware('web')->get('/__boom', function () {
            throw new BadMethodCallException('Call to undefined method App\Models\User::loanPrograms()');
        });
    }

    private function user(?string $role = null): User
    {
        $user = User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_a_super_admin_is_told_what_broke(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get('/__boom')
            ->assertStatus(500)
            ->assertInertia(fn ($page) => $page
                ->has('diagnostic', fn ($diagnostic) => $diagnostic
                    ->where('type', 'BadMethodCallException')
                    ->where('message', 'Call to undefined method App\Models\User::loanPrograms()')
                    ->where('summary', fn (string $summary) => str_contains($summary, 'method that does not exist'))
                    ->has('location')
                    ->has('frames')
                )
            );
    }

    public function test_an_ordinary_member_is_told_nothing(): void
    {
        $this->actingAs($this->user())
            ->get('/__boom')
            ->assertStatus(500)
            ->assertInertia(fn ($page) => $page
                ->missing('diagnostic')
                ->where('title', 'Something went wrong on our end')
            );
    }

    public function test_a_finance_admin_is_told_nothing_either(): void
    {
        // Administering money is not the same trust level as administering the
        // platform, and a trace names files and can quote data.
        $this->actingAs($this->user('finance_admin'))
            ->get('/__boom')
            ->assertInertia(fn ($page) => $page->missing('diagnostic'));
    }

    public function test_a_signed_out_visitor_is_told_nothing(): void
    {
        $this->get('/__boom')
            ->assertStatus(500)
            ->assertInertia(fn ($page) => $page->missing('diagnostic'));
    }

    public function test_a_livewire_request_gets_the_readable_page_rather_than_a_bare_500(): void
    {
        // Livewire renders whatever body it receives inside a modal, which is
        // how the admin panel showed a naked 500 before this.
        $response = $this->actingAs($this->user('super_admin'))
            ->withHeaders(['X-Livewire' => 'true'])
            ->get('/__boom');

        $response->assertStatus(500);
        $response->assertSee('What went wrong', false);
        $response->assertSee('Call to undefined method', false);
    }

    public function test_secrets_are_redacted_from_the_detail(): void
    {
        config(['database.connections.pgsql.password' => 'sup3r-s3cret-db-pass']);

        $diagnostic = ErrorDiagnostic::for(new RuntimeException(
            'SQLSTATE[08006] connection failed: password=sup3r-s3cret-db-pass host=127.0.0.1'
        ));

        $this->assertStringNotContainsString('sup3r-s3cret-db-pass', $diagnostic['message']);
        $this->assertStringContainsString('[redacted]', $diagnostic['message']);
    }

    public function test_the_summary_is_written_for_each_common_kind_of_failure(): void
    {
        $cases = [
            'Call to a member function name() on null' => 'expected an object',
            'Attempt to read property "email" on null' => 'read a property',
            'Permission denied' => 'refused permission',
            'Allowed memory size of 134217728 bytes exhausted' => 'out of memory',
            'Maximum execution time of 30 seconds exceeded' => 'out of time',
        ];

        foreach ($cases as $message => $expected) {
            $diagnostic = ErrorDiagnostic::for(new RuntimeException($message));
            $this->assertStringContainsString($expected, $diagnostic['summary'], "No plain-English summary for: {$message}");
        }
    }

    public function test_the_location_points_at_our_own_code_with_a_relative_path(): void
    {
        // Thrown from a real service rather than constructed in this file, so
        // the reported location is application code.
        Route::middleware('web')->get('/__boom-in-app', function () {
            app(\App\Services\LoanStateMachine::class)->transition(
                \App\Models\Loan::factory()->create(['status' => 'settled']),
                'overdue'
            );
        });

        $diagnostic = null;

        $this->actingAs($this->user('super_admin'))
            ->get('/__boom-in-app')
            ->assertInertia(function ($page) use (&$diagnostic) {
                $diagnostic = $page->toArray()['props']['diagnostic'] ?? null;
            });

        $this->assertNotNull($diagnostic);
        $this->assertStringStartsWith('app/Services/LoanStateMachine.php', $diagnostic['location']);
        $this->assertStringNotContainsString(base_path(), $diagnostic['location'], 'Paths should be relative to the project root.');

        foreach ($diagnostic['frames'] as $frame) {
            $this->assertStringNotContainsString('vendor/', $frame);
        }
    }
}
