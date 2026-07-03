<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionGuardedPage;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MergeMembers extends Page
{
    use HasPermissionGuardedPage;

    protected static string|array $permission = 'manage members';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Merge Members';
    protected static string $view = 'filament.pages.merge-members';

    public ?int $primaryId = null;
    public ?int $duplicateId = null;
    public ?array $primaryData = null;
    public ?array $duplicateData = null;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Select Records to Merge')->schema([
                Forms\Components\Select::make('primaryId')
                    ->label('Keep this record (primary)')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->limit(20)
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->loadPreview()),
                Forms\Components\Select::make('duplicateId')
                    ->label('Merge this record into primary (will be deleted)')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => User::where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->limit(20)
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->loadPreview()),
            ])->columns(2),
        ]);
    }

    public function loadPreview(): void
    {
        $this->primaryData = $this->primaryId ? User::find($this->primaryId)?->only('id', 'name', 'email', 'graduating_set_id', 'status') : null;
        $this->duplicateData = $this->duplicateId ? User::find($this->duplicateId)?->only('id', 'name', 'email', 'graduating_set_id', 'status') : null;
    }

    public function mergeRecords(): void
    {
        if (!$this->primaryId || !$this->duplicateId || $this->primaryId === $this->duplicateId) {
            Notification::make()->title('Select two different records.')->danger()->send();
            return;
        }

        $primary   = User::findOrFail($this->primaryId);
        $duplicate = User::findOrFail($this->duplicateId);

        // Re-assign related records to primary
        foreach (['forumPosts', 'forumReplies', 'blogPosts', 'payments', 'jobs', 'businessListings', 'galleryAlbums', 'skillSharings', 'notifications'] as $relation) {
            $duplicate->{$relation}()->update(['user_id' => $primary->id]);
        }

        // Carry over claimed/imported flags if primary hasn't been claimed
        if (!$primary->account_claimed && $duplicate->account_claimed) {
            $primary->update([
                'account_claimed' => true,
                'claimed_at' => $duplicate->claimed_at,
            ]);
        }

        // Merge roles
        $duplicate->roles->each(fn ($role) => $primary->assignRole($role));

        // Hard-delete the duplicate
        $duplicate->forceDelete();

        Notification::make()
            ->title("Merged #{$this->duplicateId} into #{$this->primaryId}.")
            ->success()
            ->send();

        $this->primaryId = null;
        $this->duplicateId = null;
        $this->primaryData = null;
        $this->duplicateData = null;
    }
}
