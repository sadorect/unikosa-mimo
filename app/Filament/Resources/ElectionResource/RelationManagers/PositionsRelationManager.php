<?php

namespace App\Filament\Resources\ElectionResource\RelationManagers;

use App\Filament\Concerns\HasEligibilityRulesRepeater;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PositionsRelationManager extends RelationManager
{
    use HasEligibilityRulesRepeater;

    protected static string $relationship = 'positions';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('display_order')->numeric()->default(0),
            Forms\Components\TextInput::make('winning_threshold_percent')
                ->numeric()
                ->suffix('%')
                ->helperText('Optional. Leave blank for a simple plurality winner (most votes wins). If set, the leading candidate must clear this share of votes cast for the position or the result is "no winner — threshold not met".'),

            static::eligibilityRulesRepeater('candidacy', 'Who can run for this position'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('display_order')->label('Order'),
                Tables\Columns\TextColumn::make('winning_threshold_percent')->label('Threshold')->suffix('%')->placeholder('Plurality'),
                Tables\Columns\TextColumn::make('candidates_count')->counts('candidates')->label('Candidates'),
            ])
            ->headerActions([
                // Positions must be fixed before voting opens: adding one mid-vote would
                // leave already-cast ballots missing a decision for it.
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => in_array($this->getOwnerRecord()->status, ['draft', 'nominations_open'], true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Only deletable before voting starts — deleting a position after votes
                // exist would cascade-delete those ballots and corrupt the record.
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => in_array($this->getOwnerRecord()->status, ['draft', 'nominations_open'], true)),
            ]);
    }
}
