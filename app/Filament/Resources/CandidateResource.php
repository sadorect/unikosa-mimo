<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGuardedResource;
use App\Filament\Resources\CandidateResource\Pages;
use App\Models\Candidate;
use App\Models\UnikosaNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CandidateResource extends Resource
{
    use HasPermissionGuardedResource;

    protected static string|array $permission = 'manage elections';

    protected static ?string $model = Candidate::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'Candidates';

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasAnyPermission(['manage elections', 'moderate elections']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('position_id')->relationship('position', 'title')->required()->disabled(),
            Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->disabled(),
            Forms\Components\Textarea::make('manifesto')->rows(6)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->label('')->circular(),
                Tables\Columns\TextColumn::make('user.name')->label('Candidate')->searchable(),
                Tables\Columns\TextColumn::make('position.title')->label('Position'),
                Tables\Columns\TextColumn::make('position.election.title')->label('Election'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'withdrawn' => 'gray',
                    default => 'warning',
                }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Submitted'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'withdrawn' => 'Withdrawn',
                ]),
                Tables\Filters\SelectFilter::make('position')->relationship('position', 'title'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->visible(fn (Candidate $record) => $record->status === 'pending' && Auth::user()?->can('moderate elections'))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function (Candidate $record) {
                        $record->update([
                            'status' => 'approved',
                            'feedback' => null,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        UnikosaNotification::create([
                            'user_id' => $record->user_id,
                            'type' => 'candidacy_approved',
                            'data' => [
                                'type' => 'candidacy_approved',
                                'message' => "Your candidacy for \"{$record->position->title}\" has been approved.",
                                'url' => route('elections.show', $record->position->election),
                            ],
                        ]);
                    }),
                Tables\Actions\Action::make('request_changes')
                    ->label('Request Changes')
                    ->visible(fn (Candidate $record) => $record->status === 'pending' && Auth::user()?->can('moderate elections'))
                    ->color('warning')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('feedback')
                            ->label('What needs to change?')
                            ->required(),
                    ])
                    ->action(function (Candidate $record, array $data) {
                        $record->update([
                            'status' => 'pending',
                            'feedback' => $data['feedback'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        UnikosaNotification::create([
                            'user_id' => $record->user_id,
                            'type' => 'candidacy_changes_requested',
                            'data' => [
                                'type' => 'candidacy_changes_requested',
                                'message' => "Changes were requested on your candidacy for \"{$record->position->title}\": {$data['feedback']}",
                                'url' => route('elections.show', $record->position->election),
                            ],
                        ]);
                    }),
                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->visible(fn (Candidate $record) => $record->status === 'pending' && Auth::user()?->can('moderate elections'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for denial')
                            ->required(),
                    ])
                    ->action(function (Candidate $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'feedback' => $data['reason'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        UnikosaNotification::create([
                            'user_id' => $record->user_id,
                            'type' => 'candidacy_rejected',
                            'data' => [
                                'type' => 'candidacy_rejected',
                                'message' => "Your candidacy for \"{$record->position->title}\" was not approved. Reason: {$data['reason']}",
                                'url' => route('elections.show', $record->position->election),
                            ],
                        ]);
                    }),
                // Only deletable before voting starts — deleting a candidate who has votes
                // would cascade-delete those ballots and silently corrupt the tally.
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Candidate $record) => in_array($record->position->election->status, ['draft', 'nominations_open'], true)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidates::route('/'),
            'edit' => Pages\EditCandidate::route('/{record}/edit'),
        ];
    }
}
