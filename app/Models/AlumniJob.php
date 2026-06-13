<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_jobs';

    protected $fillable = [
        'user_id', 'listing_type', 'title', 'company', 'description', 'type', 'location',
        'is_remote', 'salary_min', 'salary_max', 'salary_currency',
        'application_url', 'contact_email', 'status',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'salary_min' => 'integer',
        'salary_max' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }
}
