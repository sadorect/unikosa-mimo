<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Keeps the loan_officer / loan_disburser roles in step with the offices a
 * user actually holds.
 *
 * The `loan_program_officers` pivot says *which fund* someone may act on;
 * LoanPolicy::officerWith() then also asks whether they hold the matching
 * permission. Both halves must be true, so an officer appointed without the
 * role is appointed to nothing — they can see the fund and do none of its
 * work. That is the gap this closes.
 *
 * The office-to-role map follows the permissions, not the names:
 *
 *   reviewer   needs 'review loans'    -> loan_officer
 *   treasurer  needs 'manage loans'    -> loan_officer
 *   disburser  needs 'disburse loans'  -> loan_disburser
 *
 * Roles are recomputed from the whole pivot rather than toggled per attach,
 * so someone holding an office in two funds keeps their role when they are
 * detached from one, and losing their last such office removes it. Only these
 * two roles are ever touched — super_admin, finance_admin and the rest are
 * granted by other means and are none of this class's business.
 */
class LoanOfficerRoleSync
{
    /** Office held in a fund => the role that carries its permission. */
    protected const OFFICE_ROLES = [
        'reviewer' => 'loan_officer',
        'treasurer' => 'loan_officer',
        'disburser' => 'loan_disburser',
    ];

    public function sync(User $user): void
    {
        $offices = $user->loanProgramOfficerships()
            ->pluck('loan_program_officers.role')
            ->all();

        $wanted = collect($offices)
            ->map(fn (?string $office): ?string => self::OFFICE_ROLES[$office] ?? null)
            ->filter()
            ->unique();

        foreach (array_unique(array_values(self::OFFICE_ROLES)) as $role) {
            // firstOrCreate rather than assume: the roles arrive in a
            // migration, and a fund must not become unappointable because a
            // database was rebuilt without it.
            if ($wanted->contains($role)) {
                Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
                $user->assignRole($role);

                continue;
            }

            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
