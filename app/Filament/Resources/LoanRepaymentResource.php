<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanRepaymentResource\Pages;
use App\Models\LoanRepayment;
use App\Services\LoanRepaymentService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The treasurer's queue: money members say they have paid, waiting to be
 * matched against what the fund actually received. Nothing here moves a
 * balance until it is confirmed.
 */
class LoanRepaymentResource extends Resource
{
    protected static ?string $model = LoanRepayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Loan Repayments';
    protected static ?int $navigationSort = 24;

    public static function canViewAny(): bool
    {
        return (bool) Auth::user()?->hasAnyPermission(['manage loans', 'disburse loans']);
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

    /** Waiting confirmations, so the queue is the badge. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'pending_confirmation')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereHas('loan', fn (Builder $loan) => $loan->whereIn(
            'loan_program_id',
            $user->loanProgramOfficerships()->select('loan_programs.id')
        ));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('loan.reference')->label('Loan')->searchable(),
                Tables\Columns\TextColumn::make('loan.applicant.name')->label('Borrower')->searchable(),
                Tables\Columns\TextColumn::make('payer.name')
                    ->label('Paid by')
                    ->description(fn (LoanRepayment $record) => $record->isOnBehalfOfBorrower() ? 'Guarantor' : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('amount')->money('NGN'),
                Tables\Columns\TextColumn::make('paid_on')->date(),
                Tables\Columns\TextColumn::make('method')->formatStateUsing(fn ($state) => str($state)->headline()),
                Tables\Columns\TextColumn::make('reference')->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending_confirmation',
                        'success' => 'confirmed',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending_confirmation' => 'Awaiting confirmation',
                    'confirmed' => 'Confirmed',
                    'rejected' => 'Rejected',
                ])->default('pending_confirmation'),
            ])
            ->actions([
                Tables\Actions\Action::make('proof')
                    ->label('Proof')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (LoanRepayment $record) => $record->proof_path !== null)
                    ->url(fn (LoanRepayment $record) => route('loans.proof.repayment', [$record->loan_id, $record->id]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Confirm only once you can see the money in the fund\'s account. This is what reduces the balance.')
                    ->visible(fn (LoanRepayment $record) => $record->status === 'pending_confirmation' && Gate::allows('confirmRepayment', $record->loan))
                    ->action(function (LoanRepayment $record) {
                        try {
                            app(LoanRepaymentService::class)->confirm($record, Auth::user());
                        } catch (RuntimeException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        $loan = $record->loan->fresh();

                        Notification::make()->success()
                            ->title('Repayment confirmed.')
                            ->body($loan->status === 'settled'
                                ? 'That clears the loan — it is now settled.'
                                : 'Outstanding: '.number_format($loan->outstanding_amount).'.')
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (LoanRepayment $record) => $record->status === 'pending_confirmation' && Gate::allows('confirmRepayment', $record->loan))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Why can this not be confirmed?')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (LoanRepayment $record, array $data) {
                        try {
                            app(LoanRepaymentService::class)->reject($record, Auth::user(), $data['rejection_reason']);
                        } catch (RuntimeException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Rejected. The payer has been told why.')->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanRepayments::route('/'),
        ];
    }
}
