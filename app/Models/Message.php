<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'body'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeBetweenUsers(Builder $query, int $userAId, int $userBId): Builder
    {
        return $query->where(function (Builder $q) use ($userAId, $userBId) {
            $q->where('sender_id', $userAId)->where('recipient_id', $userBId);
        })->orWhere(function (Builder $q) use ($userAId, $userBId) {
            $q->where('sender_id', $userBId)->where('recipient_id', $userAId);
        });
    }
}
