<?php

namespace App\Filament\Resources\LoanProgramResource\RelationManagers;

use App\Models\User;
use App\Services\LoanOfficerRoleSync;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The fund's officers. This pivot is what says *which fund* someone may act
 * on, and appointing someone here is what scopes them to it. It is not on its
 * own enough: LoanPolicy::officerWith() also asks for the permission behind
 * the office, so LoanOfficerRoleSync grants the matching loan_officer /
 * loan_disburser role on attach and takes it back on detach. Both halves of
 * every appointment are written here.
 *
 * The table allows duplicates because one person may legitimately hold two
 * offices in the same fund. That is what makes each row a pivot row rather
 * than a person: Filament then keys rows by the pivot id, so editing an office
 * changes the one row and detaching removes the one office instead of every
 * office that person holds. It also stops the attach list from hiding someone
 * who is already an officer, which is how a reviewer becomes treasurer too.
 * Holding the *same* office twice is the one duplicate that means nothing, and
 * is refused on both forms.
 */
class OfficersRelationManager extends RelationManager
{
    protected static string $relationship = 'officers';

    /**
     * Stated rather than guessed. Filament derives the inverse from the owner
     * model's name — LoanProgram becomes loanPrograms() — and the relation on
     * User is called loanProgramOfficerships() because holding an office is
     * not the same as belonging to a fund. Without this, opening the attach
     * form is a 500.
     */
    protected static ?string $inverseRelationship = 'loanProgramOfficerships';

    protected static ?string $title = 'Officers';

    /** The offices a fund has, and what each one is allowed to do. */
    protected const OFFICES = [
        'reviewer' => 'Reviewer — reviews and approves applications',
        'disburser' => 'Disburser — releases the money',
        'treasurer' => 'Treasurer — confirms repayments',
    ];

    /**
     * The edit form. Only the office is editable: changing *who* holds it is
     * a detach and an attach, because the two appointments are different
     * appointments and each should be recorded as one.
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('role')
                ->label('Office')
                ->options(self::OFFICES)
                ->required()
                ->rule(fn (?Model $record): Closure => $this->officeIsNotAlreadyHeld(
                    $record?->getKey(),
                    $record?->getRelationValue('pivot')?->getKey(),
                )),
        ]);
    }

    /**
     * Refuses an office the person already holds in this fund. Two offices are
     * a real appointment; the same office twice is a second row that grants
     * nothing and has to be cleaned up by hand.
     */
    protected function officeIsNotAlreadyHeld(?int $userId, ?int $exceptPivotId = null): Closure
    {
        return function (string $attribute, $value, Closure $fail) use ($userId, $exceptPivotId): void {
            if ($userId === null) {
                return;
            }

            $query = $this->getOwnerRecord()->officers()
                ->where('users.id', $userId)
                ->wherePivot('role', $value);

            // Written as a statement, not ->when(): when() forwards off the
            // BelongsToMany to the Eloquent builder, and the wherePivot inside
            // the callback is quietly dropped — which lets the edit through to
            // the unique index and a 500.
            if ($exceptPivotId !== null) {
                $query->wherePivot('id', '!=', $exceptPivotId);
            }

            if ($query->exists()) {
                $fail('They already hold that office in this fund.');
            }
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            // Each row is an office, not a person. See the class docblock.
            ->allowDuplicates()
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('pivot.role')->label('Office'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    // Only approved members of this fund's own set can hold an
                    // office in it.
                    //
                    // The explicit select is not cosmetic. Filament joins the
                    // pivot and applies DISTINCT, and Postgres has no equality
                    // operator for `json`, so `select distinct users.*` over a
                    // users table carrying `skills` / `social_links` is a hard
                    // 42883 error the moment the attach form opens. Naming the
                    // three columns the select actually needs keeps json out of
                    // the DISTINCT.
                    ->recordSelectOptionsQuery(fn ($query) => $query
                        ->select(['users.id', 'users.name', 'users.email'])
                        ->where('status', 'approved')
                        ->where('graduating_set_id', $this->getOwnerRecord()->set_id))
                    ->form(fn (Tables\Actions\AttachAction $action) => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('role')
                            ->label('Office')
                            ->options(self::OFFICES)
                            ->required()
                            ->rule(fn (Forms\Get $get): Closure => $this->officeIsNotAlreadyHeld(
                                filled($get('recordId')) ? (int) $get('recordId') : null,
                            )),
                    ])
                    ->after(fn (User $record) => app(LoanOfficerRoleSync::class)->sync($record)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Change office')
                    ->modalHeading(fn (User $record): string => "Change {$record->name}'s office")
                    // Swapping reviewer for disburser swaps which permission
                    // they hold, so the roles are recomputed here too.
                    ->after(fn (User $record) => app(LoanOfficerRoleSync::class)->sync($record)),
                Tables\Actions\DetachAction::make()
                    ->after(fn (User $record) => app(LoanOfficerRoleSync::class)->sync($record)),
            ]);
    }

    /** Convenience for the admin: who is currently in each office. */
    public static function getBadge($ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->officers()->count();

        return $count > 0 ? (string) $count : null;
    }

    protected function canAttach(): bool
    {
        return User::query()->where('graduating_set_id', $this->getOwnerRecord()->set_id)->exists();
    }
}
