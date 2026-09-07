<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGuardedResource;
use App\Filament\Resources\SetResource\Pages;
use App\Models\Set;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SetResource extends Resource
{
    use HasPermissionGuardedResource;

    protected static string|array $permission = 'manage sets';

    protected static ?string $model = Set::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('year')->numeric()->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Select::make('rep_id')->label('Representative')->relationship('rep', 'name')->searchable()->nullable()
                ->helperText('The set\'s public/primary contact.'),
            Forms\Components\Select::make('coordinators')
                ->label('Approval coordinators')
                ->relationship('coordinators', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText('These members can approve or reject signups for this set, and are emailed when someone signs up. They are granted the Set Representative role automatically.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('year')->sortable(),
                Tables\Columns\TextColumn::make('rep.name')->label('Rep'),
                Tables\Columns\TextColumn::make('coordinators.name')->label('Coordinators')->badge()
                    ->placeholder('None — signups fall to admins'),
                Tables\Columns\TextColumn::make('members_count')->counts('members')->label('Members'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSets::route('/'),
            'create' => Pages\CreateSet::route('/create'),
            'edit' => Pages\EditSet::route('/{record}/edit'),
        ];
    }
}
