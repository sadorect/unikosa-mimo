<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A declared repayment. Only rows a treasurer or disburser has confirmed
 * reduce the balance — an applicant must not be able to clear their own
 * default simply by declaring a payment.
 */
class LoanRepayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id', 'paid_by_user_id', 'payer_role',
        'amount', 'paid_on', 'method', 'reference', 'proof_path', 'note',
        'recorded_by', 'status', 'confirmed_by', 'confirmed_at', 'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_on' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Whose money this was. A guarantor paying for a defaulting borrower is a
     * different fact from a treasurer typing the row in, which is `recorder`.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function isOnBehalfOfBorrower(): bool
    {
        return $this->payer_role === 'guarantor';
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
