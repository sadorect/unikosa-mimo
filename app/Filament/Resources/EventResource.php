<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGuardedResource;
use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use App\Models\UnikosaNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventResource extends Resource
{
    use HasPermissionGuardedResource;

    protected static string|array $permission = 'manage events';

    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasAnyPermission(['manage events', 'moderate events']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\RichEditor::make('description')->required()->columnSpanFull(),
            Forms\Components\FileUpload::make('cover_image')
                ->label('Cover Image')
                ->image()
                ->disk(config('filesystems.media_disk'))
                ->directory('events')
                ->columnSpanFull()
                // Public submissions store a ready-to-render absolute URL (matching avatar/gallery
                // uploads elsewhere), not a disk-relative path — mirror that here so the column
                // format stays consistent regardless of upload source.
                ->saveUploadedFileUsing(function ($component, $file) {
                    $disk = $component->getDiskName();
                    $path = $file->storeAs($component->getDirectory(), $component->getUploadedFileNameForStorage($file), $disk);

                    return Storage::disk($disk)->url($path);
                })
                // The stored state is already an absolute URL (see saveUploadedFileUsing above),
                // so skip Filament's default disk exists()/size()/mimeType() lookups — those
                // assume a disk-relative path and would break on a full URL.
                ->getUploadedFileUsing(fn ($file) => [
                    'name' => basename((string) $file),
                    'size' => 0,
                    'type' => null,
                    'url' => $file,
                ]),
            Forms\Components\TextInput::make('location'),
            Forms\Components\Toggle::make('is_virtual'),
            Forms\Components\TextInput::make('livestream_url'),
            Forms\Components\DateTimePicker::make('start_at')->required(),
            Forms\Components\DateTimePicker::make('end_at'),
            Forms\Components\Toggle::make('is_paid'),
            Forms\Components\TextInput::make('ticket_price')->numeric()
                ->helperText('In the smallest currency unit (e.g. 5000 = ₦50.00).'),
            Forms\Components\TextInput::make('ticket_currency'),
            Forms\Components\Select::make('chapter_id')->relationship('chapter', 'name')->nullable(),
            Forms\Components\Select::make('set_id')->relationship('set', 'name')->nullable()->searchable(),
            Forms\Components\TextInput::make('capacity')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('')->circular(false)->square(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'changes_requested' => 'info',
                    default => 'warning',
                }),
                Tables\Columns\TextColumn::make('creator.name')->label('Submitted by'),
                Tables\Columns\TextColumn::make('start_at')->dateTime(),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\IconColumn::make('is_virtual')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'changes_requested' => 'Changes requested',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->visible(fn (Event $record) => $record->status === 'pending' && Auth::user()?->can('moderate events'))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function (Event $record) {
                        $record->update([
                            'status' => 'approved',
                            'feedback' => null,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        UnikosaNotification::create([
                            'user_id' => $record->created_by,
                            'type' => 'event_approved',
                            'data' => [
                                'type' => 'event_approved',
                                'message' => "Your event \"{$record->title}\" has been approved and is now live.",
                                'url' => route('events.show', $record),
                            ],
                        ]);
                    }),
                Tables\Actions\Action::make('request_changes')
                    ->label('Request Changes')
                    ->visible(fn (Event $record) => $record->status === 'pending' && Auth::user()?->can('moderate events'))
                    ->color('warning')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('feedback')
                            ->label('What needs to change?')
                            ->required(),
                    ])
                    ->action(function (Event $record, array $data) {
                        $record->update([
                            'status' => 'changes_requested',
                            'feedback' => $data['feedback'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        UnikosaNotification::create([
                            'user_id' => $record->created_by,
                            'type' => 'event_changes_requested',
                            'data' => [
                                'type' => 'event_changes_requested',
                                'message' => "Changes were requested on your event \"{$record->title}\": {$data['feedback']}",
                                'url' => route('events.edit', $record),
                            ],
                        ]);
                    }),
                Tables\Actions\Action::make('deny')
                    ->label('Deny')
                    ->visible(fn (Event $record) => $record->status === 'pending' && Auth::user()?->can('moderate events'))
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for denial')
                            ->required(),
                    ])
                    ->action(function (Event $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'feedback' => $data['reason'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        UnikosaNotification::create([
                            'user_id' => $record->created_by,
                            'type' => 'event_rejected',
                            'data' => [
                                'type' => 'event_rejected',
                                'message' => "Your event \"{$record->title}\" was not approved. Reason: {$data['reason']}",
                                'url' => route('events.mine'),
                            ],
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
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
