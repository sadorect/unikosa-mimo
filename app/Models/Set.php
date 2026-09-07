<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Set extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'year', 'description', 'rep_id'];

    protected $casts = ['year' => 'integer'];

    public function rep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rep_id');
    }

    /**
     * Everyone authorised to review signups for this set. `rep_id` remains the
     * set's primary/public contact; coordinators are who can actually approve.
     */
    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'set_coordinators', 'set_id', 'user_id')->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'graduating_set_id');
    }
}
