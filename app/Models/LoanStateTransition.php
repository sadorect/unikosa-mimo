<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only log of every move the state machine made. Written only by
 * LoanStateMachine; never updated, never deleted.
 */
class LoanStateTransition extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['loan_id', 'from_status', 'to_status', 'actor_id', 'actor_type', 'reason', 'meta'];

    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
