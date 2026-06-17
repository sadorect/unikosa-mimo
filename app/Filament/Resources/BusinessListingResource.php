<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessListingResource\Pages;
use App\Models\BusinessListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BusinessListingResource extends Resource
{
    protected static ?string $model = BusinessListing::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\RichEditor::make('description')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('category')->required(),
            Forms\Components\TextInput::make('website'),
            Forms\Components\TextInput::make('contact_email')->email(),
            Forms\Components\TextInput::make('contact_phone'),
            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessListings::route('/'),
            'create' => Pages\CreateBusinessListing::route('/create'),
            'edit' => Pages\EditBusinessListing::route('/{record}/edit'),
        ];
    }
}
