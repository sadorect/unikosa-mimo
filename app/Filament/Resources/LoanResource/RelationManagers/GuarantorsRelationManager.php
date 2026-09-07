<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only for the committee. A guarantor's acceptance has to come from
 * their own authenticated session — it is the consent that puts their name on
 * a default notice, so no officer may record it on their behalf.
 */
class GuarantorsRelationManager extends RelationManager
{
    protected static string $relationship = 'guarantors';

    protected static ?string $title = 'Guarantors';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Member')->placeholder(fn ($record) => $record->invited_name ?? '—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'invited',
                        'success' => 'accepted',
                        'danger' => 'declined',
                        'secondary' => ['revoked', 'expired'],
                    ]),
                Tables\Columns\TextColumn::make('invited_at')->dateTime()->label('Asked'),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->label('Expires')->placeholder('—'),
                Tables\Columns\TextColumn::make('responded_at')->dateTime()->label('Answered')->placeholder('—'),
                Tables\Columns\TextColumn::make('response_note')->wrap()->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
