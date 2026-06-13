<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('description')->required(),
            Forms\Components\TextInput::make('location'),
            Forms\Components\Toggle::make('is_virtual'),
            Forms\Components\TextInput::make('livestream_url'),
            Forms\Components\DateTimePicker::make('start_at')->required(),
            Forms\Components\DateTimePicker::make('end_at'),
            Forms\Components\Toggle::make('is_paid'),
            Forms\Components\TextInput::make('ticket_price')->numeric(),
            Forms\Components\TextInput::make('ticket_currency'),
            Forms\Components\Select::make('chapter_id')->relationship('chapter', 'name')->nullable(),
            Forms\Components\TextInput::make('capacity')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('start_at')->dateTime(),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\IconColumn::make('is_virtual')->boolean(),
                Tables\Columns\TextColumn::make('status')->badge(fn (string $state): string => match ($state) {
                    'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray',
                }),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
