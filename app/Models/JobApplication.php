<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = ['job_id', 'user_id', 'cover_letter', 'cv_path', 'referral_id', 'status'];

    public function job()
    {
        return $this->belongsTo(AlumniJob::class, 'job_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }
}
