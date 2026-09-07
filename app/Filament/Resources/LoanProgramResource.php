<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGuardedResource;
use App\Filament\Resources\LoanProgramResource\Pages;
use App\Filament\Resources\LoanProgramResource\RelationManagers;
use App\Models\LoanProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Where a set configures its lending fund. Everything member-facing stays
 * dark until the `loans_enabled` setting is switched on, so this screen is
 * safe to ship ahead of the rest of the module.
 */
class LoanProgramResource extends Resource
{
    use HasPermissionGuardedResource;

    protected static string|array $permission = 'manage loans';

    protected static ?string $model = LoanProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Loan Programs';
    protected static ?int $navigationSort = 22;

    /**
     * Super admins see every fund; anyone else sees only funds they hold an
     * office in. Same shape as UserResource::getEloquentQuery().
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasRole('super_admin')) {
            return $query;
        }

        return $query->whereIn('id', $user->loanProgramOfficerships()->select('loan_programs.id'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Fund')->schema([
                Forms\Components\Select::make('set_id')
                    ->relationship('set', 'name')
                    ->required()
                    ->helperText('Only members of this set may apply.'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set, ?LoanProgram $record) => $record ? null : $set('slug', Str::slug((string) $state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(64)
                    ->helperText('Unique within the set. Leave alone once loans exist.'),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft — invisible to members',
                        'active' => 'Active — accepting applications',
                        'paused' => 'Paused — no new applications',
                        'closed' => 'Closed',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\TextInput::make('currency')->default('NGN')->maxLength(3)->required(),
                Forms\Components\TextInput::make('fund_pool')
                    ->numeric()
                    ->default(0)
                    ->helperText('Total the fund can have out on loan at once. Approvals check the remaining headroom against this.'),
            ])->columns(2),

            Forms\Components\Section::make('Loan terms')->schema([
                Forms\Components\TextInput::make('min_loan_amount')->numeric()->default(0)->required(),
                Forms\Components\TextInput::make('max_loan_amount')->numeric()->nullable()->helperText('Blank for no ceiling.'),
                Forms\Components\TextInput::make('default_tenure_months')->numeric()->default(3)->required(),
                Forms\Components\TextInput::make('max_tenure_months')->numeric()->default(3)->required(),
                Forms\Components\TextInput::make('interest_rate_bps')
                    ->label('Interest rate (basis points)')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('500 = 5.00%, flat over the whole tenure. Fixed at disbursement and never recalculated.'),
                Forms\Components\TextInput::make('required_guarantors')->numeric()->default(2)->required(),
                Forms\Components\TextInput::make('grace_days')
                    ->numeric()
                    ->default(7)
                    ->required()
                    ->helperText('Days after the due date before a loan is recorded as in default.'),
                Forms\Components\TagsInput::make('reminder_days_before')
                    ->label('Reminder days before due')
                    ->placeholder('14')
                    ->helperText('Days ahead of the due date to remind the borrower.'),
                Forms\Components\TextInput::make('max_active_loans_per_member')->numeric()->default(1)->required(),
                Forms\Components\TextInput::make('min_member_tenure_days')
                    ->numeric()
                    ->nullable()
                    ->helperText('Optional. How long an account must exist before it may apply.'),
            ])->columns(2),

            Forms\Components\Section::make('Penalties')
                ->description('Off by default — these loans already carry interest. A per-loan penalty can also be assigned by hand.')
                ->schema([
                    Forms\Components\Toggle::make('penalty_enabled')->live(),
                    Forms\Components\TextInput::make('penalty_flat_amount')->numeric()->nullable()->visible(fn (Forms\Get $get) => $get('penalty_enabled')),
                    Forms\Components\TextInput::make('penalty_rate_bps')->label('Penalty rate (basis points)')->numeric()->nullable()->visible(fn (Forms\Get $get) => $get('penalty_enabled')),
                    Forms\Components\Select::make('penalty_basis')
                        ->options(['outstanding' => 'Outstanding balance', 'principal' => 'Principal'])
                        ->nullable()
                        ->visible(fn (Forms\Get $get) => $get('penalty_enabled')),
                ])->columns(2),

            Forms\Components\Section::make('Publication of defaults')
                ->description('Publication is the fund\'s sanction. The terms of operation must state it, or there is no consent to rely on.')
                ->schema([
                    Forms\Components\Toggle::make('forum_publication_enabled')
                        ->label('Publish defaults to the members-only forum')
                        ->default(true),
                    Forms\Components\Toggle::make('public_publication_allowed')
                        ->label('Allow escalation to the public defaulters page')
                        ->default(false)
                        ->helperText('Never automatic. Even with this on, a super admin must publish each notice by hand, and the site-wide public defaulters page has its own switch.'),
                    Forms\Components\TextInput::make('publication_expiry_days')
                        ->numeric()
                        ->default(90)
                        ->required()
                        ->helperText('Every notice is taken down automatically after this many days, and immediately when the balance clears.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('set.name')->label('Set')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('fund_pool')->money('NGN')->label('Pool'),
                Tables\Columns\TextColumn::make('interest_rate_bps')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2).'%'),
                Tables\Columns\TextColumn::make('required_guarantors')->label('Guarantors'),
                Tables\Columns\TextColumn::make('loans_count')->counts('loans')->label('Loans'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'closed' => 'Closed',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OfficersRelationManager::class,
            RelationManagers\TermsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanPrograms::route('/'),
            'create' => Pages\CreateLoanProgram::route('/create'),
            'edit' => Pages\EditLoanProgram::route('/{record}/edit'),
        ];
    }
}
