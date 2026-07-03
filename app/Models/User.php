<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'date_of_birth', 'wedding_anniversary',
        'gender', 'graduating_set_id', 'house', 'country', 'city',
        'profession', 'bio', 'skills', 'social_links', 'avatar',
        'status', 'chapter_id', 'imported', 'account_claimed',
        'imported_at', 'claimed_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'wedding_anniversary' => 'date',
            'skills' => 'array',
            'social_links' => 'array',
            'imported' => 'boolean',
            'account_claimed' => 'boolean',
            'imported_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(['super_admin', 'set_representative', 'chapter_head', 'content_moderator', 'finance_admin']);
    }

    public function graduatingSet(): BelongsTo
    {
        return $this->belongsTo(Set::class, 'graduating_set_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function profilePrivacy(): HasOne
    {
        return $this->hasOne(ProfilePrivacy::class);
    }

    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    public function forumReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_rsvps')->withPivot('status', 'paid', 'payment_reference')->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AlumniJob::class);
    }

    public function businessListings(): HasMany
    {
        return $this->hasMany(BusinessListing::class);
    }

    public function galleryAlbums(): HasMany
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    public function skillSharings(): HasMany
    {
        return $this->hasMany(SkillSharing::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UnikosaNotification::class);
    }

    public function candidacies(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
