<?php

namespace Tests\Feature;

use App\Jobs\SendDuesReminders;
use App\Models\Due;
use App\Models\User;
use App\Notifications\DuesReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DuesReminderBatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_emails_go_out_in_waves_but_in_app_notices_do_not_wait(): void
    {
        Carbon::setTestNow('2026-12-01 09:00:00');
        Notification::fake();

        $size = SendDuesReminders::BATCH_SIZE;
        $members = User::factory()->count($size * 2 + 1)->create(['status' => 'approved']);
        Due::create(['name' => 'Annual Dues', 'amount' => 500000, 'currency' => 'NGN', 'frequency' => 'annual']);

        (new SendDuesReminders)->handle();

        $mailDelays = [];
        foreach ($members as $member) {
            Notification::assertSentTo($member, DuesReminderNotification::class, function ($n) use (&$mailDelays) {
                $this->assertArrayNotHasKey('database', $n->delay);
                $mailDelays[] = (int) abs($n->delay['mail']->diffInSeconds(now()));

                return true;
            });
        }

        $interval = SendDuesReminders::BATCH_INTERVAL;
        $this->assertSame($size, count(array_filter($mailDelays, fn ($d) => $d == 0)));
        $this->assertSame($size, count(array_filter($mailDelays, fn ($d) => $d == $interval)));
        $this->assertSame(1, count(array_filter($mailDelays, fn ($d) => $d == 2 * $interval)));
    }

    public function test_a_mail_server_deferral_is_retried_with_backoff(): void
    {
        $n = new DuesReminderNotification('Dues', 100, 'NGN');

        $this->assertGreaterThanOrEqual(5, $n->tries);
        $this->assertNotEmpty($n->backoff());
    }
}
