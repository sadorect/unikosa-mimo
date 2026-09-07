<?php

namespace Database\Factories;

use App\Models\LoanProgram;
use App\Models\Set;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoanProgram>
 */
class LoanProgramFactory extends Factory
{
    protected $model = LoanProgram::class;

    public function definition(): array
    {
        return [
            'set_id' => Set::factory(),
            'name' => 'Welfare Loan',
            'slug' => Str::random(12),
            'status' => 'active',
            'currency' => 'NGN',
            'fund_pool' => 5_000_000,
            'min_loan_amount' => 20_000,
            'max_loan_amount' => 500_000,
            'default_tenure_months' => 3,
            'max_tenure_months' => 3,
            'interest_rate_bps' => 500,
            'interest_method' => 'flat_per_tenure',
            'required_guarantors' => 2,
            'grace_days' => 7,
            'reminder_days_before' => [14, 7, 1],
            'max_active_loans_per_member' => 1,
            'penalty_enabled' => false,
            'set_forum_publication_enabled' => true,
            'association_forum_publication_enabled' => false,
            'publication_expiry_days' => 90,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
