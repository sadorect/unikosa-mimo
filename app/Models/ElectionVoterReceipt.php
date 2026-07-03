<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proves a member voted in an election (turnout, double-vote prevention) — and nothing
 * else. Deliberately has no relation to the votes table; that separation is what makes
 * the ballot box anonymous. Never join this model against Vote/Candidate in application code.
 */
class ElectionVoterReceipt extends Model
{
    use HasFactory;

    protected $fillable = ['election_id', 'user_id', 'voted_at'];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
