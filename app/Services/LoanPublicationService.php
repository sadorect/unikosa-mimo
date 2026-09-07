<?php

namespace App\Services;

use App\Models\ForumGroup;
use App\Models\ForumPost;
use App\Models\Loan;
use App\Models\LoanDefaultPublication;
use App\Models\User;
use App\Notifications\LoanDefaultPublished;
use App\Notifications\LoanPublicationTakenDown;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Default notices.
 *
 * Every notice lives inside the members-only forum. There is deliberately no
 * unauthenticated disclosure anywhere in this module: the association's
 * sanction is being answerable to the people you graduated with, not being
 * findable by strangers.
 *
 * Two audiences: the borrower's own set, which is automatic on default and
 * the people whose fund the money came from; and the whole association, which
 * is never automatic, needs the platform-wide permission plus the fund's own
 * switch, and cannot happen until the default has had time to be cured.
 *
 * A published notice renders a frozen snapshot (`published_payload`) and never
 * reads the live loan, so no later edit can quietly change or widen what has
 * already been disclosed.
 */
class LoanPublicationService
{
    public function __construct(protected LoanNotifier $notifier) {}

    public function publishToSetForum(Loan $loan, ?User $actor = null): ?LoanDefaultPublication
    {
        if (! $loan->program->set_forum_publication_enabled) {
            return null;
        }

        return $this->publish($loan, 'set_forum', $actor);
    }

    /**
     * The escalation. Three independent gates, and a cooling-off on top:
     * nobody is named to the whole association the day their grace window
     * closes.
     */
    public function publishToAssociationForum(Loan $loan, User $actor): ?LoanDefaultPublication
    {
        if (! $actor->can('publish loan defaults')) {
            throw new RuntimeException('You do not have permission to escalate a default notice.');
        }

        if (! $loan->program->association_forum_publication_enabled) {
            throw new RuntimeException('This fund does not allow escalation beyond its own set.');
        }

        if ($loan->status !== 'defaulted') {
            throw new RuntimeException('Only a loan in default can be published.');
        }

        $coolingOff = (int) config('loans.association_escalation_min_days');

        if ($loan->defaulted_at && $loan->defaulted_at->addDays($coolingOff)->isFuture()) {
            throw new RuntimeException("A default can only be escalated {$coolingOff} days after it is recorded.");
        }

        return $this->publish($loan, 'association_forum', $actor);
    }

    protected function publish(Loan $loan, string $scope, ?User $actor): ?LoanDefaultPublication
    {
        if ($loan->status !== 'defaulted') {
            throw new RuntimeException('Only a loan in default can be published.');
        }

        // One live notice per audience. Enforced here rather than by a partial
        // unique index, which behaves differently on Postgres and sqlite.
        $existing = $loan->publications()->scope($scope)->active()->first();

        if ($existing) {
            return $existing;
        }

        $payload = $this->payloadFor($loan);
        $author = $actor ?? $this->systemAuthorFor($loan);

        // A fund with nobody to post under simply does not publish. Failing
        // loudly here would take the default itself down with it, and a loan
        // that quietly stops being in default because the forum was
        // unavailable is far worse than a missing notice.
        if (! $author) {
            Log::warning('Loan default notice skipped: no officer to publish under.', [
                'loan_id' => $loan->id,
                'reference' => $loan->reference,
                'scope' => $scope,
            ]);

            return null;
        }

        return DB::transaction(function () use ($loan, $scope, $actor, $payload, $author) {
            $group = $this->groupFor($loan, $scope, $author);

            $post = ForumPost::create([
                'forum_group_id' => $group->id,
                'user_id' => $author->id,
                'title' => "Loan default notice — {$payload['reference']}",
                'body' => HtmlSanitizer::clean($this->bodyFor($payload)),
                // A system notice skips the moderation queue; it is posted by
                // the fund, not submitted to it.
                'status' => 'approved',
                'post_type' => 'announcement',
            ]);

            return LoanDefaultPublication::create([
                'loan_id' => $loan->id,
                'scope' => $scope,
                'forum_post_id' => $post->id,
                'published_by' => $actor?->id,
                'published_at' => now(),
                'expires_at' => now()->addDays((int) $loan->program->publication_expiry_days),
                'published_payload' => $payload,
            ]);
        });
    }

    /**
     * The whole of what is disclosed, built as an explicit array literal.
     * Never $user->toArray(), never only() on a model — those leak whatever
     * column someone adds next. Contact details, dates of birth, addresses,
     * professions, photos and the purpose of the loan are all deliberately
     * absent, even in a members-only notice.
     */
    public function payloadFor(Loan $loan): array
    {
        return [
            'reference' => $loan->reference,
            'borrower_name' => $loan->applicant->name,
            'set' => $loan->set?->name,
            'fund' => $loan->program->name,
            'currency' => $loan->currency,
            'principal' => (int) $loan->disbursed_amount,
            'outstanding' => $loan->outstanding_amount,
            'due_on' => $loan->due_at?->toDateString(),
            'days_overdue' => $loan->days_overdue,
            'defaulted_on' => $loan->defaulted_at?->toDateString(),
            // Named alongside the borrower, as the association decided and as
            // the terms they accepted say. Names only — a guarantor's contact
            // details are no more publishable than the borrower's.
            'guarantors' => $this->notifier->guarantorsOf($loan)->map(fn (User $guarantor) => [
                'name' => $guarantor->name,
                'set' => $guarantor->graduatingSet?->name,
            ])->values()->all(),
        ];
    }

    protected function bodyFor(array $payload): string
    {
        return view('loans.default-notice', ['notice' => $payload])->render();
    }

    /**
     * The set's own group by default; the association-wide group for an
     * escalation. `type` stays within the existing enum — 'interest' is the
     * only value that means "not tied to one set or chapter".
     */
    protected function groupFor(Loan $loan, string $scope, User $author): ForumGroup
    {
        if ($scope === 'association_forum') {
            return ForumGroup::firstOrCreate(
                ['slug' => 'loan-default-notices'],
                [
                    'name' => 'Loan Default Notices',
                    'description' => 'Default notices published by the association\'s loan funds.',
                    'type' => 'interest',
                    'is_moderated' => false,
                    'created_by' => $author->id,
                ]
            );
        }

        $set = $loan->set;
        $slug = 'loan-defaults-'.str($set?->name ?? "set-{$loan->set_id}")->slug();

        return ForumGroup::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => ($set?->name ?? 'Set').' — Loan Defaults',
                'description' => "Default notices for the {$set?->name} loan fund.",
                'type' => 'set',
                'set_id' => $loan->set_id,
                'is_moderated' => false,
                'created_by' => $author->id,
            ]
        );
    }

    /**
     * A notice needs an author because forum_posts.user_id is not nullable.
     * The officer who reviewed the loan is the honest answer; failing that,
     * whoever reviews for the fund.
     */
    protected function systemAuthorFor(Loan $loan): ?User
    {
        return $loan->reviewer
            ?? $loan->program->reviewers()->first()
            ?? $loan->program->officers()->first()
            ?? User::role('super_admin')->first();
    }

    public function takedown(LoanDefaultPublication $publication, ?User $actor, string $reason): void
    {
        if ($publication->taken_down_at !== null) {
            return;
        }

        DB::transaction(function () use ($publication, $actor, $reason) {
            $publication->update([
                'taken_down_at' => now(),
                'taken_down_by' => $actor?->id,
                'takedown_reason' => $reason,
            ]);

            $publication->forumPost?->delete();
        });
    }

    /**
     * Every live notice for a loan, gone. This is the cure path: it runs
     * inside settlement and write-off, so clearing the balance removes the
     * notice rather than merely stopping the next one.
     */
    public function takedownAllFor(Loan $loan, ?User $actor, string $reason): int
    {
        $publications = $loan->publications()->active()->get();

        foreach ($publications as $publication) {
            $this->takedown($publication, $actor, $reason);
        }

        if ($publications->isNotEmpty()) {
            foreach ($this->notifier->applicantAndGuarantors($loan) as $recipient) {
                $this->notifier->send(
                    $recipient,
                    new LoanPublicationTakenDown($loan, $reason),
                    'loan_publication_taken_down',
                    "The default notice for loan {$loan->reference} has been removed.",
                    route('loans.show', $loan)
                );
            }
        }

        return $publications->count();
    }

    /** Tell the people named that they have been. */
    public function announce(Loan $loan, LoanDefaultPublication $publication): void
    {
        foreach ($this->notifier->applicantAndGuarantors($loan) as $recipient) {
            $this->notifier->send(
                $recipient,
                new LoanDefaultPublished($loan, $publication),
                'loan_default_published',
                $publication->scope === 'association_forum'
                    ? "A default notice for loan {$loan->reference} has been published to the whole association."
                    : "A default notice for loan {$loan->reference} has been published to your set's forum.",
                route('loans.show', $loan)
            );
        }
    }
}
