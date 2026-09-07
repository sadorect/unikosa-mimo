<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use App\Services\LoanDisbursementService;
use App\Services\LoanStateMachine;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * The welfare committee's queue.
 *
 * Read-only as a resource: loans are never created or edited here, only moved
 * through their states by the actions below, each of which goes through
 * LoanStateMachine so the transition log and the notifications stay in step.
 */
class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Loans';
    protected static ?int $navigationSort = 23;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return (bool) $user?->hasAnyPermission(['manage loans', 'review loans', 'disburse loans']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * Super admins see every loan; everyone else sees only the funds they hold
     * an office in. An officer of one set must never see another set's money.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereIn('loan_program_id', $user->loanProgramOfficerships()->select('loan_programs.id'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Application')->schema([
                Infolists\Components\TextEntry::make('reference')->copyable(),
                Infolists\Components\TextEntry::make('status')->badge(),
                Infolists\Components\TextEntry::make('applicant.name')->label('Applicant'),
                Infolists\Components\TextEntry::make('set.name')->label('Set'),
                Infolists\Components\TextEntry::make('program.name')->label('Fund'),
                Infolists\Components\TextEntry::make('amount_requested')->money('NGN'),
                Infolists\Components\TextEntry::make('amount_approved')->money('NGN')->placeholder('—'),
                Infolists\Components\TextEntry::make('tenure_months')->placeholder('—'),
                Infolists\Components\TextEntry::make('purpose')->columnSpanFull(),
            ])->columns(3),

            Infolists\Components\Section::make('Agreement')->schema([
                Infolists\Components\TextEntry::make('term.version')->label('Terms version')->placeholder('—'),
                Infolists\Components\TextEntry::make('terms_agreed_at')->dateTime()->placeholder('—'),
                // Kept visible because it is the evidence behind the
                // publication penalty, not a diagnostic.
                Infolists\Components\TextEntry::make('terms_agreed_ip')->label('Accepted from IP')->placeholder('—'),
            ])->columns(3),

            Infolists\Components\Section::make('Disbursement')
                ->visible(fn (Loan $record) => $record->disburser_id !== null)
                ->schema([
                    Infolists\Components\TextEntry::make('disburser.name')->label('Disburser')->placeholder('—'),
                    Infolists\Components\TextEntry::make('disburser_notified_at')->dateTime()->label('Released')->placeholder('—'),
                    Infolists\Components\TextEntry::make('disburser_acknowledged_at')->dateTime()->label('Acknowledged')->placeholder('—'),
                    Infolists\Components\TextEntry::make('disbursed_amount')->money('NGN')->placeholder('—'),
                    Infolists\Components\TextEntry::make('disbursed_at')->date()->placeholder('—'),
                    Infolists\Components\TextEntry::make('disbursement_reference')->placeholder('—'),
                    Infolists\Components\TextEntry::make('due_at')->date()->placeholder('—'),
                    Infolists\Components\TextEntry::make('grace_ends_at')->date()->label('Grace ends')->placeholder('—'),
                    Infolists\Components\TextEntry::make('total_repayable')->money('NGN')->placeholder('—'),
                ])->columns(3),

            Infolists\Components\Section::make('Review')->schema([
                Infolists\Components\TextEntry::make('reviewer.name')->label('Reviewer')->placeholder('—'),
                Infolists\Components\TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                Infolists\Components\TextEntry::make('review_notes')->columnSpanFull()->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('applicant.name')->label('Applicant')->searchable(),
                Tables\Columns\TextColumn::make('set.name')->label('Set')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => ['draft', 'withdrawn', 'cancelled', 'written_off'],
                        'warning' => ['submitted', 'under_review', 'changes_requested', 'guarantors_pending', 'overdue', 'in_grace'],
                        'success' => ['approved', 'guarantors_secured', 'disbursed', 'settled'],
                        'danger' => ['rejected', 'defaulted'],
                    ]),
                Tables\Columns\TextColumn::make('amount_requested')->label('Requested')->money('NGN'),
                Tables\Columns\TextColumn::make('amount_approved')->label('Approved')->money('NGN')->placeholder('—'),
                Tables\Columns\TextColumn::make('due_at')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Applied')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(collect(config('loans.statuses'))->mapWithKeys(fn ($s) => [$s => str($s)->headline()->toString()])->all()),
                Tables\Filters\SelectFilter::make('loan_program_id')->relationship('program', 'name')->label('Fund'),
            ])
            ->actions([
                Tables\Actions\Action::make('claim')
                    ->label('Start review')
                    ->icon('heroicon-o-hand-raised')
                    ->visible(fn (Loan $record) => $record->status === 'submitted' && Gate::allows('review', $record))
                    ->action(function (Loan $record) {
                        app(LoanStateMachine::class)->transition($record, 'under_review', Auth::user(), [
                            'attributes' => ['reviewed_by' => Auth::id(), 'reviewed_at' => now()],
                        ]);

                        Notification::make()->success()->title('You are now reviewing this application.')->send();
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Loan $record) => $record->status === 'under_review' && Gate::allows('review', $record))
                    ->form(fn (Loan $record) => [
                        Forms\Components\TextInput::make('amount_approved')
                            ->numeric()
                            ->required()
                            ->default($record->amount_requested)
                            ->helperText('May be less than the '.number_format($record->amount_requested).' requested. The fund has '
                                .number_format($record->program->availableHeadroom()).' uncommitted.'),
                        Forms\Components\TextInput::make('tenure_months')
                            ->numeric()
                            ->required()
                            ->default($record->tenure_months ?? $record->program->default_tenure_months)
                            ->maxValue($record->program->max_tenure_months),
                        Forms\Components\Textarea::make('review_notes')->label('Note to the applicant')->maxLength(2000),
                    ])
                    ->action(function (Loan $record, array $data) {
                        $program = $record->program;
                        $amount = (int) $data['amount_approved'];

                        // Headroom is checked here, not only at application:
                        // other loans may have been approved in the meantime.
                        if ($amount > $program->availableHeadroom() + (int) $record->amount_requested) {
                            Notification::make()->danger()
                                ->title('The fund does not have that much uncommitted.')
                                ->body('Uncommitted: '.number_format($program->availableHeadroom()).'.')
                                ->send();

                            return;
                        }

                        $machine = app(LoanStateMachine::class);

                        // Rate, tenure and guarantor count are snapshotted now,
                        // so later edits to the fund never move a live loan.
                        $machine->transition($record, 'approved', Auth::user(), [
                            'silent' => true,
                            'reason' => $data['review_notes'] ?? null,
                            'attributes' => [
                                'amount_approved' => $amount,
                                'tenure_months' => (int) $data['tenure_months'],
                                'interest_rate_bps' => $program->interest_rate_bps,
                                'guarantors_required' => $program->required_guarantors,
                                'review_notes' => $data['review_notes'] ?? null,
                                'reviewed_by' => Auth::id(),
                                'reviewed_at' => now(),
                            ],
                        ]);

                        // Approval and the request for guarantors are one step
                        // for the applicant; the second transition carries the
                        // message.
                        $machine->transition($record, 'guarantors_pending', Auth::user());

                        Notification::make()->success()->title('Approved. The applicant has been asked for guarantors.')->send();
                    }),

                Tables\Actions\Action::make('request_changes')
                    ->label('Request changes')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (Loan $record) => $record->status === 'under_review' && Gate::allows('review', $record))
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('What does the applicant need to change?')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (Loan $record, array $data) {
                        app(LoanStateMachine::class)->transition($record, 'changes_requested', Auth::user(), [
                            'reason' => $data['review_notes'],
                            'attributes' => ['review_notes' => $data['review_notes']],
                        ]);

                        Notification::make()->success()->title('Sent back to the applicant.')->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Rejection is final. If the application only needs correcting, use "Request changes" instead.')
                    ->visible(fn (Loan $record) => in_array($record->status, ['submitted', 'under_review'], true) && Gate::allows('review', $record))
                    ->form([
                        Forms\Components\Textarea::make('review_notes')->label('Reason')->required()->maxLength(2000),
                    ])
                    ->action(function (Loan $record, array $data) {
                        app(LoanStateMachine::class)->transition($record, 'rejected', Auth::user(), [
                            'reason' => $data['review_notes'],
                            'attributes' => [
                                'review_notes' => $data['review_notes'],
                                'rejected_at' => now(),
                                'reviewed_by' => Auth::id(),
                                'reviewed_at' => now(),
                            ],
                        ]);

                        Notification::make()->success()->title('Application rejected.')->send();
                    }),

                Tables\Actions\Action::make('release_to_disburser')
                    ->label('Release to disburser')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Loan $record) => $record->status === 'guarantors_secured' && Gate::allows('review', $record))
                    ->form(fn (Loan $record) => [
                        Forms\Components\Select::make('disburser_id')
                            ->label('Disburser')
                            ->options($record->program->disbursers()->pluck('users.name', 'users.id'))
                            ->default($record->program->disbursers()->value('users.id'))
                            ->required()
                            ->helperText('Appointed on the fund. Add a disburser there first if this list is empty.'),
                    ])
                    ->action(function (Loan $record, array $data) {
                        try {
                            app(LoanDisbursementService::class)->release(
                                $record,
                                Auth::user(),
                                \App\Models\User::find($data['disburser_id'])
                            );
                        } catch (RuntimeException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Released. The disburser has been notified.')->send();
                    }),

                Tables\Actions\Action::make('acknowledge')
                    ->label('Acknowledge notice')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->visible(fn (Loan $record) => $record->status === 'disburser_notified' && Gate::allows('disburse', $record))
                    ->requiresConfirmation()
                    ->modalDescription('Confirms you have received the notice to disburse. You will record the transfer itself separately.')
                    ->action(function (Loan $record) {
                        try {
                            app(LoanDisbursementService::class)->acknowledge($record, Auth::user());
                        } catch (RuntimeException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Acknowledged.')->send();
                    }),

                Tables\Actions\Action::make('record_disbursement')
                    ->label('Record disbursement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Loan $record) => $record->status === 'disburser_acknowledged' && Gate::allows('disburse', $record))
                    ->form(fn (Loan $record) => [
                        Forms\Components\TextInput::make('disbursed_amount')
                            ->numeric()
                            ->required()
                            ->default($record->amount_approved)
                            ->maxValue((int) $record->amount_approved),
                        Forms\Components\DatePicker::make('disbursed_at')
                            ->label('Date the transfer was made')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->helperText('The payback clock is counted from this date, not from today.'),
                        Forms\Components\Select::make('disbursement_method')
                            ->options(collect(config('loans.disbursement_methods'))->mapWithKeys(fn ($m) => [$m => str($m)->headline()->toString()])->all())
                            ->default('bank_transfer')
                            ->required(),
                        Forms\Components\TextInput::make('disbursement_reference')->label('Bank/transfer reference')->maxLength(255),
                        Forms\Components\FileUpload::make('disbursement_proof_path')
                            ->label('Proof of transfer')
                            // Private disk: this file carries bank details and
                            // must never be reachable under /storage.
                            ->disk(config('loans.private_disk'))
                            ->directory('loan-disbursements')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(config('loans.proof_max_kb')),
                    ])
                    ->action(function (Loan $record, array $data) {
                        try {
                            app(LoanDisbursementService::class)->recordDisbursement($record, Auth::user(), $data);
                        } catch (RuntimeException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()
                            ->title('Disbursement recorded.')
                            ->body('Repayment is due '.$record->fresh()->due_at?->toFormattedDayDateString().'.')
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GuarantorsRelationManager::class,
            RelationManagers\TransitionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'view' => Pages\ViewLoan::route('/{record}'),
        ];
    }
}
