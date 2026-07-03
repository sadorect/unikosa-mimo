<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Gates a Resource's list/create/edit/delete actions behind one or more
 * spatie/laravel-permission permission names declared as static::$permission.
 */
trait HasPermissionGuardedResource
{
    protected static function userHasResourcePermission(): bool
    {
        return Auth::user()?->hasAnyPermission((array) static::$permission) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::userHasResourcePermission();
    }

    public static function canCreate(): bool
    {
        return static::userHasResourcePermission();
    }

    public static function canEdit($record): bool
    {
        return static::userHasResourcePermission();
    }

    public static function canDelete($record): bool
    {
        return static::userHasResourcePermission();
    }

    public static function canDeleteAny(): bool
    {
        return static::userHasResourcePermission();
    }
}
