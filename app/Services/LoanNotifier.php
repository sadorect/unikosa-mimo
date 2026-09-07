<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\UnikosaNotification;
use App\Models\User;
use App\Notifications\LoanApproved;
use App\Notifications\LoanChangesRequested;
use App\Notifications\LoanRejected;
use App\Notifications\LoanSubmittedForReview;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Every loan notification goes out as a mail notification plus an explicitly
 * written bell row.
 *
 * The native `database` channel cannot be used here: notifications.type is
 * fillable and the uuid the channel supplies for `id` is dropped by
 * mass-assignment guarding, so a database-channel row lands with a class name
 * in `type` and no `url` — a bell entry that links nowhere. Writing the row
 * ourselves is the pattern used by EventController::sendRsvpConfirmation()
 * and PublishElectionResults, and it is the only one that produces a working
 * link.
 */
class LoanNotifier
{
    /**
     * Fan a transition out to whoever needs to hear about it. Called by
     * LoanStateMachine once the transaction has committed.
     */
    public function onTransition(Loan $loan, string $from, string $to, ?User $actor, array $options = []): void
    {
        match ($to) {
            'submitted' => $this->reviewersOf($loan)->each(fn (User $reviewer) => $this->send(
                $reviewer,
                new LoanSubmittedForReview($loan),
                'loan_submitted',
                "{$loan->applicant->name} applied for a {$loan->currency} ".number_format($loan->amount_requested).' loan.',
                route('loans.show', $loan)
            )),

            'changes_requested' => $this->send(
                $loan->applicant,
                new LoanChangesRequested($loan),
                'loan_changes_requested',
                "Your loan application {$loan->reference} needs changes before it can be reviewed.",
                route('loans.show', $loan)
            ),

            'rejected' => $this->send(
                $loan->applicant,
                new LoanRejected($loan),
                'loan_rejected',
                "Your loan application {$loan->reference} was not approved.",
                route('loans.show', $loan)
            ),

            'guarantors_pending' => $this->send(
                $loan->applicant,
                new LoanApproved($loan),
                'loan_approved',
                "Your loan {$loan->reference} was approved. Nominate {$loan->guarantors_required} guarantors to continue.",
                route('loans.show', $loan)
            ),

            // under_review and approved are deliberately quiet: `under_review`
            // only records that a reviewer picked the application up, and
            // `approved` is immediately followed by `guarantors_pending`,
            // which carries the message the applicant actually needs.
            default => null,
        };
    }

    /** Everyone who reviews applications for this loan's fund. */
    public function reviewersOf(Loan $loan): Collection
    {
        return $loan->program->reviewers()->get();
    }

    /**
     * One recipient: the email, then the bell row that links to it.
     */
    public function send(?User $user, Notification $notification, string $type, string $message, string $url): void
    {
        if (! $user) {
            return;
        }

        $user->notify($notification);

        UnikosaNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'data' => [
                'type' => $type,
                'message' => $message,
                'url' => $url,
            ],
        ]);
    }
}
