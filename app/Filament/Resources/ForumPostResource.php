<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ForumPostResource\Pages;
use App\Models\ForumPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ForumPostResource extends Resource
{
    protected static ?string $model = ForumPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('body')->required()->rows(6),
            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('author.name')->label('Author'),
                Tables\Columns\TextColumn::make('group.name')->label('Group'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForumPosts::route('/'),
            'create' => Pages\CreateForumPost::route('/create'),
            'edit' => Pages\EditForumPost::route('/{record}/edit'),
        ];
    }
}
