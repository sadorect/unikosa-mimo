<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An anonymous ballot. Deliberately has NO voter-identifying column, no relation to
 * ElectionVoterReceipt, a random UUID primary key (so insertion order can't be
 * reconstructed) and no timestamps (so it can't be correlated to a receipt's
 * voted_at). This table must never be joinable back to the voter who cast it, and is
 * only ever queried in aggregate (counts/tallies).
 */
class Vote extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = ['position_id', 'candidate_id', 'is_abstention'];

    protected $casts = [
        'is_abstention' => 'boolean',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
