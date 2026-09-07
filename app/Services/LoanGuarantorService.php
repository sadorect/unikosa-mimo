<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\User;
use App\Notifications\LoanGuarantorInvited;
use App\Notifications\LoanGuarantorResponded;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Inviting guarantors and recording what they said.
 *
 * v1 requires an approved member of the loan's own set. The sanction behind
 * this scheme is publication on a members-only forum, which is inert against
 * someone with no account; publishing the name of a person who never held one
 * and never consented through an authenticated session is a real privacy
 * exposure; and accept/decline has to be a non-forgeable authenticated action,
 * which an emailed token to a stranger is not. The invited_email/invited_name
 * columns exist so that a v2 "invite a non-member, they register first" flow
 * is a code change rather than a migration.
 */
class LoanGuarantorService
{
    public function __construct(
        protected LoanStateMachine $stateMachine,
        protected LoanNotifier $notifier,
    ) {}

    /** @return string[] Why this member cannot stand for this loan. */
    public function invitationBlockers(Loan $loan, User $candidate): array
    {
        $blockers = [];

        if ($candidate->id === $loan->user_id) {
            $blockers[] = 'You cannot stand as your own guarantor.';
        }

        if ($candidate->status !== 'approved') {
            $blockers[] = 'Only approved members can act as guarantors.';
        }

        if ((int) $candidate->graduating_set_id !== (int) $loan->set_id) {
            $blockers[] = 'Guarantors must belong to the same set as the fund.';
        }

        if ($loan->guarantors()->where('user_id', $candidate->id)->whereIn('status', ['invited', 'accepted'])->exists()) {
            $blockers[] = 'That member has already been asked.';
        }

        // Someone in default themselves is in no position to stand for anyone.
        $inDefault = Loan::where('user_id', $candidate->id)
            ->whereIn('status', ['defaulted', 'written_off'])
            ->exists();

        if ($inDefault) {
            $blockers[] = 'That member has a loan in default and cannot stand as a guarantor.';
        }

        return $blockers;
    }

    /** Members of this set who could still be asked. */
    public function candidates(Loan $loan, ?string $search = null, int $limit = 20): iterable
    {
        $asked = $loan->guarantors()->whereIn('status', ['invited', 'accepted'])->pluck('user_id')->filter();

        return User::where('status', 'approved')
            ->where('graduating_set_id', $loan->set_id)
            ->where('id', '!=', $loan->user_id)
            ->whereNotIn('id', $asked)
            ->when($search, fn ($query) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%']))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'profession']);
    }

    public function invite(Loan $loan, User $candidate, User $invitedBy): LoanGuarantor
    {
        if ($loan->status !== 'guarantors_pending') {
            throw new RuntimeException('This loan is not collecting guarantors.');
        }

        $blockers = $this->invitationBlockers($loan, $candidate);

        if ($blockers !== []) {
            throw new RuntimeException($blockers[0]);
        }

        if ($loan->guarantors()->where('status', 'accepted')->count() >= $loan->guarantors_required) {
            throw new RuntimeException('This loan already has all the guarantors it needs.');
        }

        // A previously declined or expired invitation is reopened rather than
        // duplicated: unique(loan_id, user_id) would reject a second row.
        $guarantor = LoanGuarantor::updateOrCreate(
            ['loan_id' => $loan->id, 'user_id' => $candidate->id],
            [
                'invited_by' => $invitedBy->id,
                'invited_at' => now(),
                'expires_at' => now()->addDays(config('loans.guarantor_invite_days')),
                'status' => 'invited',
                'responded_at' => null,
                'response_note' => null,
            ]
        );

        $this->notifier->send(
            $candidate,
            new LoanGuarantorInvited($loan, $guarantor),
            'loan_guarantor_invited',
            "{$loan->applicant->name} has asked you to guarantee a loan.",
            route('loans.guarantorships')
        );

        return $guarantor;
    }

    /**
     * Record an acceptance or a decline, and advance the loan when the last
     * guarantor it needs has said yes.
     */
    public function respond(LoanGuarantor $guarantor, User $actor, bool $accepted, ?string $note = null, ?string $ip = null): void
    {
        if ($guarantor->status !== 'invited') {
            throw new RuntimeException('You have already responded to this request.');
        }

        if ($guarantor->hasExpired()) {
            $guarantor->update(['status' => 'expired']);

            throw new RuntimeException('This request has expired. Ask the applicant to send it again.');
        }

        $loan = $guarantor->loan;

        if ($loan->status !== 'guarantors_pending') {
            throw new RuntimeException('This loan is no longer collecting guarantors.');
        }

        DB::transaction(function () use ($guarantor, $accepted, $note, $ip) {
            $guarantor->update([
                'status' => $accepted ? 'accepted' : 'declined',
                'responded_at' => now(),
                'response_note' => $note,
                'agreed_terms_at' => $accepted ? now() : null,
                'agreed_ip' => $accepted ? $ip : null,
            ]);
        });

        $this->notifier->send(
            $loan->applicant,
            new LoanGuarantorResponded($loan, $guarantor->fresh()),
            'loan_guarantor_responded',
            $accepted
                ? "{$actor->name} has agreed to guarantee your loan {$loan->reference}."
                : "{$actor->name} declined to guarantee your loan {$loan->reference}.",
            route('loans.show', $loan)
        );

        if (! $accepted) {
            return;
        }

        $accepted = $loan->guarantors()->where('status', 'accepted')->count();

        if ($accepted >= $loan->guarantors_required) {
            // System transition: nobody clicked anything, the last acceptance
            // simply completed the set.
            $this->stateMachine->transition($loan->fresh(), 'guarantors_secured', null, [
                'attributes' => ['guarantors_secured_at' => now()],
            ]);
        }
    }

    /**
     * Close any invitation still outstanding. Called when a loan leaves the
     * guarantor stage by any route other than success, so nobody is asked to
     * stand for an application that no longer exists.
     */
    public function revokeOutstanding(Loan $loan): int
    {
        return $loan->guarantors()->where('status', 'invited')->update([
            'status' => 'revoked',
            'responded_at' => now(),
        ]);
    }

    /** Invitations nobody answered inside the window. */
    public function expireStale(): int
    {
        return LoanGuarantor::where('status', 'invited')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
