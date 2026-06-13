<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Due extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'amount', 'currency', 'frequency', 'set_id', 'chapter_id'];

    protected $casts = ['amount' => 'integer'];

    public function set()
    {
        return $this->belongsTo(Set::class);
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}
