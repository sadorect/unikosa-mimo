<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'position_id', 'user_id', 'manifesto', 'photo',
        'status', 'feedback', 'reviewed_by', 'reviewed_at', 'withdrawn_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    protected function manifesto(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => $value === null ? null : HtmlSanitizer::clean($value));
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
