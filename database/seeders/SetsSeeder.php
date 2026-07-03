<?php

namespace Database\Seeders;

use App\Models\Set;
use Illuminate\Database\Seeder;

class SetsSeeder extends Seeder
{
    public function run(): void
    {
        for ($year = 1991; $year <= 2026; $year++) {
            Set::firstOrCreate(
                ['year' => $year],
                ['name' => "Set of {$year}"]
            );
        }
    }
}
