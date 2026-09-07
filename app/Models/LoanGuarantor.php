<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanGuarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'user_id', 'invited_email', 'invited_name', 'invited_by',
        'invited_at', 'expires_at', 'status', 'responded_at', 'response_note',
        'agreed_terms_at', 'agreed_ip',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'agreed_terms_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'invited';
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** The name to show for this guarantor, member or not. */
    public function displayName(): string
    {
        return $this->user?->name ?? (string) $this->invited_name;
    }
}
