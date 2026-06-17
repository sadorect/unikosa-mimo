<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'location', 'is_virtual',
        'livestream_url', 'start_at', 'end_at', 'is_paid',
        'ticket_price', 'ticket_currency', 'chapter_id', 'created_by', 'capacity',
    ];

    protected function description(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => HtmlSanitizer::clean($value));
    }

    protected $casts = [
        'is_virtual' => 'boolean',
        'is_paid' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'ticket_price' => 'integer',
        'capacity' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rsvps(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_rsvps')->withPivot('status', 'paid', 'payment_reference')->withTimestamps();
    }
}
