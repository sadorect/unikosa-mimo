<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanRepayment>
 */
class LoanRepaymentFactory extends Factory
{
    protected $model = LoanRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => 25_000,
            'paid_on' => now()->toDateString(),
            'method' => 'bank_transfer',
            'status' => 'pending_confirmation',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed', 'confirmed_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected', 'rejection_reason' => 'No matching transfer found']);
    }
}
