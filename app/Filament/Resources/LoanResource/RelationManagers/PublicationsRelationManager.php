<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * What has been published about this loan, and what came down when. Read-only:
 * publishing and takedown are actions on the loan itself, so every disclosure
 * goes through LoanPublicationService and leaves a payload behind.
 */
class PublicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'publications';

    protected static ?string $title = 'Default notices';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\BadgeColumn::make('scope')
                    ->formatStateUsing(fn ($state) => $state === 'association_forum' ? 'Association forum' : 'Set forum')
                    ->colors(['warning' => 'set_forum', 'danger' => 'association_forum']),
                Tables\Columns\TextColumn::make('published_at')->dateTime(),
                Tables\Columns\TextColumn::make('publisher.name')->label('By')->placeholder('System'),
                Tables\Columns\TextColumn::make('expires_at')->dateTime(),
                Tables\Columns\TextColumn::make('taken_down_at')->dateTime()->label('Taken down')->placeholder('Live'),
                Tables\Columns\TextColumn::make('takedown_reason')->wrap()->placeholder('—'),
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
