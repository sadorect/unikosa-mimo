<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkillSharing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'title', 'description', 'type', 'is_paid', 'rate'];

    protected $casts = ['is_paid' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
