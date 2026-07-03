<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGuardedResource;
use App\Filament\Resources\DueResource\Pages;
use App\Models\Due;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DueResource extends Resource
{
    use HasPermissionGuardedResource;

    protected static string|array $permission = 'manage payments';

    protected static ?string $model = Due::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('amount')->numeric()->required(),
            Forms\Components\TextInput::make('currency')->default('NGN'),
            Forms\Components\Select::make('frequency')->options([
                'annual' => 'Annual', 'monthly' => 'Monthly', 'one_time' => 'One Time', 'event_based' => 'Event Based',
            ])->required(),
            Forms\Components\Select::make('set_id')->relationship('set', 'name')->nullable(),
            Forms\Components\Select::make('chapter_id')->relationship('chapter', 'name')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('NGN'),
                Tables\Columns\TextColumn::make('frequency'),
                Tables\Columns\TextColumn::make('set.name')->label('Set'),
                Tables\Columns\TextColumn::make('chapter.name')->label('Chapter'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDues::route('/'),
            'create' => Pages\CreateDue::route('/create'),
            'edit' => Pages\EditDue::route('/{record}/edit'),
        ];
    }
}
