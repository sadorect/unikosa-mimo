<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'slug', 'description', 'category', 'website', 'contact_email', 'contact_phone', 'logo', 'status'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (BusinessListing $listing) {
            if (empty($listing->slug)) {
                $listing->slug = Str::slug($listing->name);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
