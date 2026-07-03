<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EligibilityRule extends Model
{
    use HasFactory;

    protected $fillable = ['rulable_type', 'rulable_id', 'context', 'type', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function rulable(): MorphTo
    {
        return $this->morphTo();
    }
}
