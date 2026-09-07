<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A published version of a fund's terms of operation. Bodies are stored as
 * sanitised HTML (App\Support\HtmlSanitizer) and are never edited in place
 * once loans reference them — a correction means a new version.
 */
class LoanTerm extends Model
{
    use HasFactory;

    protected $fillable = ['loan_program_id', 'version', 'body', 'published_at', 'created_by'];

    protected $casts = ['published_at' => 'datetime'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoanProgram::class, 'loan_program_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
