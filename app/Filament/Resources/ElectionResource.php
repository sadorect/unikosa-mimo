<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasEligibilityRulesRepeater;
use App\Filament\Concerns\HasPermissionGuardedResource;
use App\Filament\Resources\ElectionResource\Pages;
use App\Filament\Resources\ElectionResource\RelationManagers\PositionsRelationManager;
use App\Jobs\PublishElectionResults;
use App\Models\Election;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ElectionResource extends Resource
{
    use HasPermissionGuardedResource;
    use HasEligibilityRulesRepeater;

    protected static string|array $permission = 'manage elections';

    protected static ?string $model = Election::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasAnyPermission(['manage elections', 'moderate elections']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\RichEditor::make('description')->columnSpanFull(),

            Forms\Components\Section::make('Nomination window')->schema([
                Forms\Components\DateTimePicker::make('nominations_start_at'),
                Forms\Components\DateTimePicker::make('nominations_end_at'),
            ])->columns(2),

            Forms\Components\Section::make('Voting window')->schema([
                Forms\Components\DateTimePicker::make('voting_start_at'),
                Forms\Components\DateTimePicker::make('voting_end_at'),
            ])->columns(2),

            static::eligibilityRulesRepeater('voting', 'Who can vote'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'voting_open' => 'success',
                    'nominations_open' => 'info',
                    'closed' => 'warning',
                    'results_published' => 'success',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('positions_count')->counts('positions')->label('Positions'),
                Tables\Columns\TextColumn::make('voting_start_at')->dateTime()->label('Voting starts'),
                Tables\Columns\TextColumn::make('voting_end_at')->dateTime()->label('Voting ends'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'nominations_open' => 'Nominations open',
                    'voting_open' => 'Voting open',
                    'closed' => 'Closed',
                    'results_published' => 'Results published',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open_nominations')
                    ->label('Open Nominations')
                    ->visible(fn (Election $record) => $record->status === 'draft' && Auth::user()?->can('manage elections'))
                    ->requiresConfirmation()
                    ->color('info')
                    ->icon('heroicon-o-megaphone')
                    ->disabled(fn (Election $record) => ! $record->nominations_start_at || ! $record->nominations_end_at)
                    ->action(fn (Election $record) => $record->update(['status' => 'nominations_open'])),

                Tables\Actions\Action::make('open_voting')
                    ->label('Open Voting')
                    ->visible(fn (Election $record) => $record->status === 'nominations_open' && Auth::user()?->can('manage elections'))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-envelope-open')
                    ->disabled(fn (Election $record) => ! $record->voting_start_at || ! $record->voting_end_at)
                    ->action(fn (Election $record) => $record->update(['status' => 'voting_open'])),

                Tables\Actions\Action::make('close_voting')
                    ->label('Close Voting')
                    ->visible(fn (Election $record) => $record->status === 'voting_open' && Auth::user()?->can('manage elections'))
                    ->requiresConfirmation()
                    ->color('warning')
                    ->icon('heroicon-o-lock-closed')
                    ->action(fn (Election $record) => $record->update(['status' => 'closed'])),

                Tables\Actions\Action::make('publish_results')
                    ->label('Publish Results')
                    ->visible(fn (Election $record) => $record->status === 'closed' && Auth::user()?->can('moderate elections'))
                    ->requiresConfirmation()
                    ->modalDescription('This is irreversible and will immediately email and notify every eligible voter with the outcome. Results also become publicly visible on the site.')
                    ->color('success')
                    ->icon('heroicon-o-trophy')
                    ->action(function (Election $record) {
                        $record->update(['status' => 'results_published', 'results_published_at' => now()]);
                        PublishElectionResults::dispatch($record);
                    }),

                Tables\Actions\Action::make('view_results')
                    ->label('Results')
                    ->visible(fn (Election $record) => in_array($record->status, ['closed', 'results_published'], true) && Auth::user()?->hasAnyPermission(['manage elections', 'moderate elections']))
                    ->color('gray')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Election $record) => static::getUrl('results', ['record' => $record])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PositionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListElections::route('/'),
            'create' => Pages\CreateElection::route('/create'),
            'edit' => Pages\EditElection::route('/{record}/edit'),
            'results' => Pages\ViewElectionResults::route('/{record}/results'),
        ];
    }
}
