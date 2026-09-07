<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * This loan's repayment history. Confirming happens in the Loan Repayments
 * queue, so nothing here writes.
 */
class RepaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'repayments';

    protected static ?string $title = 'Repayments';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('paid_on', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('paid_on')->date(),
                Tables\Columns\TextColumn::make('payer.name')
                    ->label('Paid by')
                    ->description(fn ($record) => $record->isOnBehalfOfBorrower() ? 'Guarantor' : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('amount')->money('NGN'),
                Tables\Columns\TextColumn::make('method')->formatStateUsing(fn ($state) => str($state)->headline()),
                Tables\Columns\TextColumn::make('reference')->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['warning' => 'pending_confirmation', 'success' => 'confirmed', 'danger' => 'rejected']),
                Tables\Columns\TextColumn::make('confirmer.name')->label('Confirmed by')->placeholder('—'),
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
