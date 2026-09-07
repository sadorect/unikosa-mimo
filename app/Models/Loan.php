<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The aggregate. Its `status` is written only by App\Services\LoanStateMachine
 * — never call $loan->update(['status' => ...]) anywhere else, or the
 * transition log and the notifications that hang off it silently diverge.
 */
class Loan extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'reference', 'loan_program_id', 'set_id', 'user_id',
        'amount_requested', 'amount_approved', 'currency', 'tenure_months',
        'interest_rate_bps', 'interest_amount', 'total_repayable', 'purpose',
        'loan_term_id', 'terms_agreed_at', 'terms_agreed_ip', 'terms_agreed_user_agent',
        'status',
        'reviewed_by', 'reviewed_at', 'review_notes', 'rejected_at', 'withdrawn_at', 'cancelled_at',
        'guarantors_required', 'guarantors_secured_at',
        'disburser_id', 'disburser_notified_at', 'disburser_acknowledged_at',
        'disbursed_amount', 'disbursed_at', 'disbursement_method', 'disbursement_reference',
        'disbursement_proof_path', 'disbursed_by',
        'due_at', 'grace_ends_at', 'last_reminder_sent_at', 'guarantors_nudged_at',
        'amount_repaid', 'penalty_amount', 'penalty_reason', 'penalty_applied_by', 'penalty_applied_at',
        'defaulted_at', 'settled_at', 'written_off_at', 'closure_note',
    ];

    protected $casts = [
        'amount_requested' => 'integer',
        'amount_approved' => 'integer',
        'tenure_months' => 'integer',
        'interest_rate_bps' => 'integer',
        'interest_amount' => 'integer',
        'total_repayable' => 'integer',
        'terms_agreed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'guarantors_required' => 'integer',
        'guarantors_secured_at' => 'datetime',
        'disburser_notified_at' => 'datetime',
        'disburser_acknowledged_at' => 'datetime',
        'disbursed_amount' => 'integer',
        'disbursed_at' => 'date',
        'due_at' => 'date',
        'grace_ends_at' => 'date',
        'last_reminder_sent_at' => 'datetime',
        'guarantors_nudged_at' => 'datetime',
        'amount_repaid' => 'integer',
        'penalty_amount' => 'integer',
        'penalty_applied_at' => 'datetime',
        'defaulted_at' => 'datetime',
        'settled_at' => 'datetime',
        'written_off_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoanProgram::class, 'loan_program_id');
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(LoanTerm::class, 'loan_term_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disburser_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(LoanGuarantor::class);
    }

    public function acceptedGuarantors(): HasMany
    {
        return $this->guarantors()->where('status', 'accepted');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(LoanStateTransition::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(LoanDefaultPublication::class);
    }

    /** Loans still owing something: disbursed and not yet closed. */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ['disbursed', 'overdue', 'in_grace', 'defaulted']);
    }

    public function scopeForSet(Builder $query, int $setId): Builder
    {
        return $query->where('set_id', $setId);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, config('loans.terminal_statuses'), true);
    }

    public function isPreDisbursement(): bool
    {
        return in_array($this->status, config('loans.pre_disbursement_statuses'), true);
    }

    /**
     * Total confirmed toward this loan. Recomputed from the repayment rows
     * every time — `amount_repaid` on the row is a display cache only.
     */
    public function confirmedRepaid(): int
    {
        return (int) $this->repayments()->confirmed()->sum('amount');
    }

    public function getOutstandingAmountAttribute(): int
    {
        $due = (int) ($this->total_repayable ?? 0) + (int) $this->penalty_amount;

        return max(0, $due - $this->confirmedRepaid());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && $this->outstanding_amount > 0;
    }

    public function getDaysOverdueAttribute(): int
    {
        if ($this->due_at === null || $this->due_at->isFuture()) {
            return 0;
        }

        return $this->due_at->diffInDays(now()->startOfDay());
    }
}
