<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The loan's own history, read-only. activity_log holds the generic field
 * diff for compliance; this is the version a human can follow.
 */
class TransitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transitions';

    protected static ?string $title = 'History';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->dateTime(),
                Tables\Columns\TextColumn::make('from_status')->label('From')->placeholder('—'),
                Tables\Columns\TextColumn::make('to_status')->label('To'),
                Tables\Columns\TextColumn::make('actor.name')->label('By')->placeholder('System'),
                Tables\Columns\TextColumn::make('reason')->wrap()->placeholder('—'),
            ])
            ->paginated([25, 50])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
