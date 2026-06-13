<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 22;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('payment_reference')->label('Reference')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money(fn (Payment $record) => $record->currency),
                Tables\Columns\TextColumn::make('payment_method')->label('Method')->badge(),
                Tables\Columns\TextColumn::make('status')->badge(fn (string $state): string => match ($state) {
                    'successful' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('paid_at')->dateTime(),
                Tables\Columns\TextColumn::make('payable_type')->label('Type')->formatStateUsing(fn ($state) => class_basename($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['successful' => 'Successful', 'pending' => 'Pending', 'failed' => 'Failed']),
                Tables\Filters\SelectFilter::make('payment_method')->options(['paystack' => 'Paystack', 'stripe' => 'Stripe']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
