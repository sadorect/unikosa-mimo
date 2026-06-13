<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account Information')->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('email')->email()->required(),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\TextInput::make('password')->password()->hiddenOn('edit'),
            ]),
            Forms\Components\Section::make('Personal Details')->schema([
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\Select::make('gender')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                Forms\Components\TextInput::make('profession'),
                Forms\Components\Textarea::make('bio')->rows(3),
            ]),
            Forms\Components\Section::make('Alumni Details')->schema([
                Forms\Components\Select::make('graduating_set_id')->relationship('graduatingSet', 'name')->searchable(),
                Forms\Components\TextInput::make('house'),
                Forms\Components\Select::make('chapter_id')->relationship('chapter', 'name')->searchable(),
                Forms\Components\TextInput::make('country'),
                Forms\Components\TextInput::make('city'),
            ]),
            Forms\Components\Section::make('Status & Role')->schema([
                Forms\Components\Select::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])->required(),
                Forms\Components\Select::make('roles')->relationship('roles', 'name')->multiple()->preload(),
            ]),
            Forms\Components\Section::make('Import Status')->schema([
                Forms\Components\Toggle::make('imported')->label('Imported Record'),
                Forms\Components\Toggle::make('account_claimed')->label('Account Claimed'),
                Forms\Components\DateTimePicker::make('imported_at')->readOnly(),
                Forms\Components\DateTimePicker::make('claimed_at')->readOnly(),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('graduatingSet.name')->label('Set'),
                Tables\Columns\TextColumn::make('chapter.name')->label('Chapter'),
                Tables\Columns\TextColumn::make('status')->badge(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\IconColumn::make('imported')->boolean()->label('Imported'),
                Tables\Columns\IconColumn::make('account_claimed')->boolean()->label('Claimed'),
                Tables\Columns\TextColumn::make('roles.name')->label('Role')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('graduating_set_id')->relationship('graduatingSet', 'name'),
                Tables\Filters\SelectFilter::make('chapter_id')->relationship('chapter', 'name'),
                Tables\Filters\TernaryFilter::make('imported')->label('Imported'),
                Tables\Filters\TernaryFilter::make('account_claimed')->label('Claimed'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status === 'pending')
                    ->action(function (User $record): void {
                        $record->update(['status' => 'approved']);
                        if (!$record->hasRole('member')) {
                            $record->assignRole('member');
                        }
                        $record->notify(new \App\Notifications\MemberApprovedNotification());
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->status === 'pending')
                    ->action(fn (User $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                $record->update(['status' => 'approved']);
                                if (!$record->hasRole('member')) {
                                    $record->assignRole('member');
                                }
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['status' => 'rejected']))
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
