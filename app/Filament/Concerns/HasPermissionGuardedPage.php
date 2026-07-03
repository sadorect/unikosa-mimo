<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Gates a Filament Page behind one or more spatie/laravel-permission
 * permission names declared as static::$permission.
 */
trait HasPermissionGuardedPage
{
    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyPermission((array) static::$permission) ?? false;
    }
}
