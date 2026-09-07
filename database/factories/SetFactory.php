<?php

namespace Database\Factories;

use App\Models\Set;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Set>
 */
class SetFactory extends Factory
{
    protected $model = Set::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(1970, 2024);

        return [
            'name' => "Set of {$year}",
            'year' => $year,
        ];
    }
}
