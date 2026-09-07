<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Loan>
 *
 * The escalation tests need loans sitting at a given point on the payback
 * clock, so the states below set the same columns the state machine would
 * have set on the way there.
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $program = LoanProgram::factory();

        return [
            'reference' => 'LN-'.now()->year.'-'.Str::upper(Str::random(8)),
            'loan_program_id' => $program,
            'set_id' => fn (array $attributes) => LoanProgram::find($attributes['loan_program_id'])?->set_id,
            'user_id' => User::factory(),
            'amount_requested' => 100_000,
            'currency' => 'NGN',
            'interest_rate_bps' => 500,
            'purpose' => fake()->sentence(),
            'status' => 'draft',
            'guarantors_required' => 2,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'amount_approved' => $attributes['amount_requested'],
            'tenure_months' => 3,
            'reviewed_at' => now(),
        ]);
    }

    /** Money out, clock running, nothing repaid yet. */
    public function disbursed(int $daysAgo = 1): static
    {
        return $this->state(function (array $attributes) use ($daysAgo) {
            $principal = $attributes['amount_requested'];
            $interest = intdiv($principal * $attributes['interest_rate_bps'], 10000);
            $disbursedAt = now()->subDays($daysAgo)->startOfDay();

            return [
                'status' => 'disbursed',
                'amount_approved' => $principal,
                'tenure_months' => 3,
                'interest_amount' => $interest,
                'total_repayable' => $principal + $interest,
                'reviewed_at' => now()->subDays($daysAgo + 3),
                'guarantors_secured_at' => now()->subDays($daysAgo + 2),
                'disburser_acknowledged_at' => now()->subDays($daysAgo + 1),
                'disbursed_amount' => $principal,
                'disbursed_at' => $disbursedAt,
                'disbursement_method' => 'bank_transfer',
                'due_at' => $disbursedAt->copy()->addMonthsNoOverflow(3),
                'grace_ends_at' => $disbursedAt->copy()->addMonthsNoOverflow(3)->addDays(7),
            ];
        });
    }

    /** Past the due date, inside the grace window. */
    public function overdue(): static
    {
        return $this->disbursed(95)->state(fn () => ['status' => 'overdue']);
    }

    /** Past the grace window with a balance still owing. */
    public function defaulted(): static
    {
        return $this->disbursed(120)->state(fn () => [
            'status' => 'defaulted',
            'defaulted_at' => now()->subDay(),
        ]);
    }
}
