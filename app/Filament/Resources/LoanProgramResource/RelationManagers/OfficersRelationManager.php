<?php

namespace App\Filament\Resources\LoanProgramResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The fund's officers. This pivot — not the loan_officer / loan_disburser
 * role — is what authorisation checks read, so appointing someone here is
 * what actually grants them anything. The role is synced alongside only to
 * open the admin panel for them.
 */
class OfficersRelationManager extends RelationManager
{
    protected static string $relationship = 'officers';

    protected static ?string $title = 'Officers';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('role')
                ->options([
                    'reviewer' => 'Reviewer — reviews and approves applications',
                    'disburser' => 'Disburser — releases the money',
                    'treasurer' => 'Treasurer — confirms repayments',
                ])
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('pivot.role')->label('Office'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    // Only approved members of this fund's own set can hold an
                    // office in it.
                    ->recordSelectOptionsQuery(fn ($query) => $query
                        ->where('status', 'approved')
                        ->where('graduating_set_id', $this->getOwnerRecord()->set_id))
                    ->form(fn (Tables\Actions\AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('role')
                            ->options([
                                'reviewer' => 'Reviewer',
                                'disburser' => 'Disburser',
                                'treasurer' => 'Treasurer',
                            ])
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ]);
    }

    /** Convenience for the admin: who is currently in each office. */
    public static function getBadge($ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->officers()->count();

        return $count > 0 ? (string) $count : null;
    }

    protected function canAttach(): bool
    {
        return User::query()->where('graduating_set_id', $this->getOwnerRecord()->set_id)->exists();
    }
}
