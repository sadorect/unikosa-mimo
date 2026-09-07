<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\LoanGuarantor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanGuarantor>
 */
class LoanGuarantorFactory extends Factory
{
    protected $model = LoanGuarantor::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'user_id' => User::factory(),
            'invited_at' => now(),
            'expires_at' => now()->addDays(config('loans.guarantor_invite_days')),
            'status' => 'invited',
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => 'accepted',
            'responded_at' => now(),
            'agreed_terms_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => 'declined', 'responded_at' => now()]);
    }
}
