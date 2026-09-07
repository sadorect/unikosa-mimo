<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A published default notice, forum-scoped or public. The rendered page reads
 * published_payload and nothing else, so a later edit to the loan cannot
 * change what has already been disclosed.
 */
class LoanDefaultPublication extends Model
{
    protected $fillable = [
        'loan_id', 'scope', 'forum_post_id', 'public_slug', 'published_by',
        'published_at', 'expires_at', 'taken_down_at', 'taken_down_by',
        'takedown_reason', 'published_payload',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'taken_down_at' => 'datetime',
        'published_payload' => 'array',
    ];

    /** Live notices: not taken down and not yet expired. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('taken_down_at')->where('expires_at', '>', now());
    }

    public function scopeScope(Builder $query, string $scope): Builder
    {
        return $query->where('scope', $scope);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function forumPost(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'forum_post_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isLive(): bool
    {
        return $this->taken_down_at === null && $this->expires_at?->isFuture();
    }
}
