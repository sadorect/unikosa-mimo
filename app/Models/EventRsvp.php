<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRsvp extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'user_id', 'status', 'paid', 'payment_reference'];

    protected $casts = ['paid' => 'boolean'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
