<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['election_id', 'title', 'description', 'display_order', 'winning_threshold_percent'];

    protected $casts = [
        'display_order' => 'integer',
        'winning_threshold_percent' => 'decimal:2',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function approvedCandidates(): HasMany
    {
        return $this->candidates()->where('status', 'approved')->whereNull('withdrawn_at');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function eligibilityRules(): MorphMany
    {
        return $this->morphMany(EligibilityRule::class, 'rulable');
    }
}
