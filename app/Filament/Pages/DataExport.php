<?php

namespace App\Filament\Pages;

use App\Exports\UserExport;
use App\Filament\Concerns\HasPermissionGuardedPage;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class DataExport extends Page
{
    use HasPermissionGuardedPage;

    protected static string|array $permission = 'manage members';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Data Export';
    protected static string $view = 'filament.pages.data-export';

    public function exportUsers()
    {
        return Excel::download(new UserExport, 'unikosa_users_' . now()->format('Y-m-d') . '.xlsx');
    }
}
