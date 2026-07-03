<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Election extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'title', 'slug', 'description',
        'nominations_start_at', 'nominations_end_at', 'voting_start_at', 'voting_end_at',
        'status', 'results_published_at', 'results_notified_at', 'created_by',
    ];

    protected $casts = [
        'nominations_start_at' => 'datetime',
        'nominations_end_at' => 'datetime',
        'voting_start_at' => 'datetime',
        'voting_end_at' => 'datetime',
        'results_published_at' => 'datetime',
        'results_notified_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Election $election) {
            if (empty($election->slug)) {
                $election->slug = Str::slug($election->title);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)->orderBy('display_order');
    }

    public function voterReceipts(): HasMany
    {
        return $this->hasMany(ElectionVoterReceipt::class);
    }

    public function eligibilityRules(): MorphMany
    {
        return $this->morphMany(EligibilityRule::class, 'rulable');
    }

    public function isNominationWindowOpen(): bool
    {
        return $this->status === 'nominations_open'
            && $this->nominations_start_at
            && $this->nominations_end_at
            && now()->between($this->nominations_start_at, $this->nominations_end_at);
    }

    public function isVotingWindowOpen(): bool
    {
        return $this->status === 'voting_open'
            && $this->voting_start_at
            && $this->voting_end_at
            && now()->between($this->voting_start_at, $this->voting_end_at);
    }
}
