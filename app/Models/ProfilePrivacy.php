<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilePrivacy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'show_email', 'show_phone', 'show_dob',
        'show_profession', 'show_skills', 'show_social_links', 'show_location',
    ];

    protected $casts = [
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'show_dob' => 'boolean',
        'show_profession' => 'boolean',
        'show_skills' => 'boolean',
        'show_social_links' => 'boolean',
        'show_location' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
