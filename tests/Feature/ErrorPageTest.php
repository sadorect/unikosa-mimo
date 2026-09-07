<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\ErrorPage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Error pages are suppressed in the testing environment so developers
        // see stack traces; this is the switch that exercises what a member
        // would actually be served.
        config(['app.force_error_pages' => true]);
    }

    private function member(): User
    {
        return User::factory()->create(['status' => 'approved', 'email_verified_at' => now()]);
    }

    public function test_an_unknown_url_renders_the_custom_page_not_a_framework_default(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertStatus(404);
        $response->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 404)
            ->where('title', 'Page not found')
        );
    }

    public function test_the_applications_own_explanation_is_shown_instead_of_the_generic_wording(): void
    {
        Route::middleware('web')->get('/__abort-with-message', fn () => abort(403, 'You have already voted in this election.'));

        $this->actingAs($this->member())
            ->get('/__abort-with-message')
            ->assertStatus(403)
            ->assertInertia(fn ($page) => $page
                ->component('Error')
                ->where('message', 'You have already voted in this election.')
            );
    }

    public function test_a_bare_abort_falls_back_to_the_written_wording(): void
    {
        Route::middleware('web')->get('/__bare-abort', fn () => abort(403));

        $this->actingAs($this->member())
            ->get('/__bare-abort')
            ->assertStatus(403)
            ->assertInertia(fn ($page) => $page->where('title', "You don't have access to this page"));
    }

    public function test_a_server_error_never_leaks_its_own_message(): void
    {
        // The message on a 500 is written by whatever broke, so it can carry a
        // file path, a query or a credential. It must never reach the page.
        $page = ErrorPage::for(500, new HttpException(500, 'SQLSTATE[08006] password authentication failed for user "unikosa"'));

        $this->assertSame('Something went wrong on our end', $page['title']);
        $this->assertStringNotContainsString('password', $page['message']);
        $this->assertStringNotContainsString('SQLSTATE', $page['message']);
    }

    public function test_symfony_boilerplate_is_replaced_rather_than_shown(): void
    {
        $page = ErrorPage::for(404, new HttpException(404, 'Not Found'));

        $this->assertSame("We couldn't find what you're looking for. It may have been moved or deleted, or the link might be incorrect.", $page['message']);
    }

    /**
     * Found on the live site rather than in this file: Laravel's own "The
     * route x could not be found" was reaching members, which tells them
     * nothing and tells everyone else how the app is put together.
     *
     */
    #[DataProvider('internalMessages')]
    public function test_framework_internals_never_reach_the_page(string $message): void
    {
        $page = ErrorPage::for(404, new HttpException(404, $message));

        $this->assertNotSame($message, $page['message'], "[{$message}] was shown to the member.");
        $this->assertSame('Page not found', $page['title']);
    }

    public static function internalMessages(): array
    {
        return [
            'routing' => ['The route no-such-page could not be found.'],
            'model binding' => ['No query results for model [App\Models\Loan] 5'],
            'method' => ['The GET method is not supported for this route. Supported methods: POST.'],
            'class name' => ['App\Policies\LoanPolicy::review returned false'],
            'named route' => ['Route [loans.missing] not defined.'],
            'sql' => ['SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "loans" does not exist'],
            'php notice' => ['Attempt to read property "name" on null'],
            'path' => ['/var/www/app/Http/Controllers/LoanController.php'],
        ];
    }

    #[DataProvider('writtenMessages')]
    public function test_messages_written_for_members_are_shown(string $message): void
    {
        $page = ErrorPage::for(403, new HttpException(403, $message));

        $this->assertSame($message, $page['message']);
    }

    public static function writtenMessages(): array
    {
        return [
            'election' => ['You have already voted in this election.'],
            'module' => ['This module is currently disabled.'],
            'loans' => ['Only the applicant can nominate guarantors.'],
            'terms' => ['This fund has not published its terms of operation.'],
        ];
    }

    public function test_a_disabled_module_explains_itself(): void
    {
        Setting::set('blog_enabled', 'false', 'modules');

        $this->actingAs($this->member())
            ->get('/blog')
            ->assertStatus(503)
            ->assertInertia(fn ($page) => $page
                ->where('status', 503)
                ->where('message', 'This module is currently disabled.')
            );
    }

    public function test_every_handled_status_has_written_copy(): void
    {
        foreach (ErrorPage::HANDLED as $status) {
            $page = ErrorPage::for($status);

            $this->assertNotSame('Something went wrong', $page['title'], "Status {$status} has no written title.");
            $this->assertNotEmpty($page['message'], "Status {$status} has no written message.");
        }
    }

    public function test_the_page_tells_a_signed_in_member_apart_from_a_visitor(): void
    {
        Route::middleware('web')->get('/__forbidden', fn () => abort(403));

        // The visitor case first: actingAs() persists for the rest of the test.
        $this->get('/__forbidden')
            ->assertInertia(fn ($page) => $page->where('signedIn', false));

        $this->actingAs($this->member())
            ->get('/__forbidden')
            ->assertInertia(fn ($page) => $page->where('signedIn', true));
    }

    public function test_error_pages_are_kept_out_of_search_indexes(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_the_static_fallback_renders_without_touching_the_database(): void
    {
        // What a member gets when the database itself is what broke, so the
        // Inertia page cannot be built. It must not query anything.
        $html = view('errors.fallback', ErrorPage::for(500))->render();

        $this->assertStringContainsString('Something went wrong on our end', $html);
        $this->assertStringContainsString('noindex', $html);
        $this->assertStringContainsString(config('app.name'), $html);
    }

    public function test_the_framework_rendered_views_exist_for_maintenance_and_failure(): void
    {
        foreach ([403, 404, 500, 503] as $status) {
            $this->assertTrue(view()->exists("errors.{$status}"), "errors/{$status}.blade.php is missing.");
            $this->assertStringContainsString('noindex', view("errors.{$status}")->render());
        }
    }

    public function test_api_routes_still_receive_json_not_a_page(): void
    {
        $this->getJson('/api/v1/this-does-not-exist')->assertStatus(404)->assertJsonStructure(['message']);
    }
}
