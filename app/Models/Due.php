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

    /**
     * Dues that apply to a member: association-wide ones plus their set's and
     * chapter's. Each branch is guarded because where('x', null) compiles to
     * "x IS NULL", which would hand a member with no chapter every set-only due.
     */
    public function scopeApplicableTo($query, User $user)
    {
        return $query->where(function ($query) use ($user) {
            $query->where(fn ($q) => $q->whereNull('set_id')->whereNull('chapter_id'));
            if ($user->graduating_set_id) {
                $query->orWhere('set_id', $user->graduating_set_id);
            }
            if ($user->chapter_id) {
                $query->orWhere('chapter_id', $user->chapter_id);
            }
        });
    }
}
