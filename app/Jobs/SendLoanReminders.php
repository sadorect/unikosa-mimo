<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Notifications\LoanDueSoon;
use App\Services\LoanNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Courtesy reminders ahead of the due date, at the offsets each fund
 * configures (14, 7 and 1 days by default).
 *
 * Only the borrower is reminded here. Guarantors are not troubled until the
 * due date has actually passed — a scheme that emails someone's guarantors a
 * fortnight early would not survive its first month.
 */
class SendLoanReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // No auto-retry: a partial run that retries would re-mail whoever it had
    // already reached. The same-day guard below covers a re-dispatch.
    public int $tries = 1;

    public function handle(LoanNotifier $notifier): void
    {
        $today = now()->startOfDay();

        Loan::query()
            ->where('status', 'disbursed')
            ->whereNotNull('due_at')
            ->where('due_at', '>=', $today)
            ->with(['program', 'applicant'])
            ->chunkById(200, function ($loans) use ($notifier, $today) {
                foreach ($loans as $loan) {
                    $offsets = $loan->program->reminder_days_before ?: [14, 7, 1];
                    // Carbon 3 returns a float here; the offsets are integers
                    // and the comparison below is strict.
                    $daysAway = (int) round($today->diffInDays($loan->due_at->copy()->startOfDay(), false));

                    if (! in_array($daysAway, array_map('intval', $offsets), true)) {
                        continue;
                    }

                    if ($loan->outstanding_amount <= 0) {
                        continue;
                    }

                    // One reminder a day at most, however often this job runs.
                    if ($loan->last_reminder_sent_at && $loan->last_reminder_sent_at->isSameDay($today)) {
                        continue;
                    }

                    $notifier->send(
                        $loan->applicant,
                        new LoanDueSoon($loan, $daysAway),
                        'loan_due_soon',
                        "Your loan {$loan->reference} is due in {$daysAway} ".str('day')->plural($daysAway).'.',
                        route('loans.show', $loan)
                    );

                    $loan->forceFill(['last_reminder_sent_at' => now()])->saveQuietly();
                }
            });
    }
}
