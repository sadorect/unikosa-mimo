<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Livewire\Features\SupportReleaseTokens\ReleaseToken;
use Livewire\Mechanisms\ComponentRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Scanners post forged snapshots to /livewire/update (155 hit the admin login on
 * 2026-11-05). Livewire rejecting them is the defence working; it must answer a
 * 4xx and stay out of the error log, whatever APP_DEBUG says.
 */
class LivewireTamperTest extends TestCase
{
    use RefreshDatabase;

    public static function debugModes(): array
    {
        return ['debug off' => [false], 'debug on' => [true]];
    }

    private function forge(array $snapshotOverrides = [], array $updates = [])
    {
        $snapshot = array_replace_recursive([
            'data' => ['captchaId' => null],
            'memo' => [
                'id' => 'abc',
                'name' => app(ComponentRegistry::class)->getName(Login::class),
                'release' => ReleaseToken::generate(Login::class),
                'path' => 'admin/login', 'method' => 'GET', 'children' => [], 'scripts' => [],
                'assets' => [], 'errors' => [], 'locale' => 'en',
            ],
            'checksum' => 'forged',
        ], $snapshotOverrides);

        return $this->withSession(['_token' => 'tok'])
            ->withHeaders(['X-Livewire' => 'true', 'X-CSRF-TOKEN' => 'tok'])
            ->postJson(app('livewire')->getUpdateUri(), [
                'components' => [['snapshot' => json_encode($snapshot), 'updates' => $updates, 'calls' => []]],
            ]);
    }

    #[DataProvider('debugModes')]
    public function test_a_forged_snapshot_is_a_quiet_client_error(bool $debug): void
    {
        config(['app.debug' => $debug]);
        $errors = 0;
        $this->app['events']->listen(MessageLogged::class, function ($e) use (&$errors) {
            $errors += $e->level === 'error' ? 1 : 0;
        });

        $status = $this->forge()->getStatusCode();

        $this->assertContains($status, [400, 419]);
        $this->assertSame(0, $errors, 'a tampered payload should not be logged as an error');
    }
}
