<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One lending fund, belonging to one graduating set. Everything an operator
 * may want to tune lives here rather than in config, so a second set can run
 * the same module on different numbers without a code change.
 */
class LoanProgram extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'set_id', 'name', 'slug', 'status', 'currency',
        'fund_pool', 'min_loan_amount', 'max_loan_amount',
        'default_tenure_months', 'max_tenure_months',
        'interest_rate_bps', 'interest_method',
        'required_guarantors', 'grace_days', 'reminder_days_before',
        'max_active_loans_per_member', 'min_member_tenure_days',
        'penalty_enabled', 'penalty_flat_amount', 'penalty_rate_bps', 'penalty_basis',
        'set_forum_publication_enabled', 'association_forum_publication_enabled', 'publication_expiry_days',
    ];

    protected $casts = [
        'fund_pool' => 'integer',
        'min_loan_amount' => 'integer',
        'max_loan_amount' => 'integer',
        'default_tenure_months' => 'integer',
        'max_tenure_months' => 'integer',
        'interest_rate_bps' => 'integer',
        'required_guarantors' => 'integer',
        'grace_days' => 'integer',
        'reminder_days_before' => 'array',
        'max_active_loans_per_member' => 'integer',
        'min_member_tenure_days' => 'integer',
        'penalty_enabled' => 'boolean',
        'penalty_flat_amount' => 'integer',
        'penalty_rate_bps' => 'integer',
        'set_forum_publication_enabled' => 'boolean',
        'association_forum_publication_enabled' => 'boolean',
        'publication_expiry_days' => 'integer',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(LoanTerm::class);
    }

    /**
     * The terms version an application should be bound to: the most recently
     * published one. Unpublished drafts are never offered to a member.
     */
    public function currentTerm(): ?LoanTerm
    {
        return $this->terms()
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'loan_program_officers', 'loan_program_id', 'user_id')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    public function officersWithRole(string $role): BelongsToMany
    {
        return $this->officers()->wherePivot('role', $role);
    }

    public function reviewers(): BelongsToMany
    {
        return $this->officersWithRole('reviewer');
    }

    public function disbursers(): BelongsToMany
    {
        return $this->officersWithRole('disburser');
    }

    public function treasurers(): BelongsToMany
    {
        return $this->officersWithRole('treasurer');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * What is left of the pool once every loan that has not reached a terminal
     * state is accounted for. Approvals check against this, not fund_pool.
     *
     * COALESCE is ANSI and behaves the same on Postgres and sqlite; nothing
     * driver-specific belongs in this query.
     */
    public function availableHeadroom(): int
    {
        $committed = (int) $this->loans()
            ->whereNotIn('status', config('loans.terminal_statuses'))
            ->selectRaw('COALESCE(SUM(COALESCE(amount_approved, amount_requested)), 0) AS committed')
            ->value('committed');

        return $this->fund_pool - $committed;
    }
}
