<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanStateTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * The only writer of loans.status.
 *
 * Calling $loan->update(['status' => ...]) anywhere else silently skips the
 * transition log and the notifications hanging off it, so the audit trail and
 * what members were told start to disagree — which, for a scheme whose only
 * sanction is publication, is the failure that matters most.
 *
 * Each transition is one update() plus one log row inside a transaction, and
 * side effects run only once that has committed.
 */
class LoanStateMachine
{
    public function __construct(protected LoanNotifier $notifier) {}

    public function allowedFrom(string $status): array
    {
        return config("loans.transitions.{$status}", []);
    }

    public function canTransition(Loan $loan, string $to): bool
    {
        return in_array($to, $this->allowedFrom($loan->status), true);
    }

    /**
     * Move a loan to a new state.
     *
     * @param  array{attributes?: array<string, mixed>, reason?: string, meta?: array<string, mixed>, actor_type?: string, silent?: bool}  $options
     *
     * @throws RuntimeException when the move is not in the transition map.
     */
    public function transition(Loan $loan, string $to, ?User $actor = null, array $options = []): Loan
    {
        if (! in_array($to, config('loans.statuses'), true)) {
            throw new InvalidArgumentException("Unknown loan status [{$to}].");
        }

        $from = $loan->status;

        if (! $this->canTransition($loan, $to)) {
            throw new RuntimeException("A loan cannot move from [{$from}] to [{$to}].");
        }

        DB::transaction(function () use ($loan, $from, $to, $actor, $options) {
            // One update per transition, not several small saves: the Auditable
            // trait logs every write, and a chatty state machine turns
            // activity_log into noise nobody can read.
            $loan->update(array_merge($options['attributes'] ?? [], ['status' => $to]));

            LoanStateTransition::create([
                'loan_id' => $loan->id,
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $actor?->id,
                'actor_type' => $options['actor_type'] ?? ($actor ? 'user' : 'system'),
                'reason' => $options['reason'] ?? null,
                'meta' => $options['meta'] ?? null,
                'created_at' => now(),
            ]);
        });

        // After commit, deliberately: a queued notification must never race a
        // transaction that has not landed yet.
        if (! ($options['silent'] ?? false)) {
            $this->notifier->onTransition($loan->refresh(), $from, $to, $actor, $options);
        }

        return $loan;
    }
}
