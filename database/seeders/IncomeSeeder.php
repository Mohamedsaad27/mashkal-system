<?php

namespace Database\Seeders;

use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Patient;
use Illuminate\Database\Seeder;

/**
 * Demo income for local development only.
 */
class IncomeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = IncomeSource::all();
        $patients = Patient::all();

        Income::factory()
            ->count(80)
            ->sequence(fn (): array => [
                'income_source_id' => $sources->random()->id,
                'patient_id' => fake()->boolean(70) ? $patients->random()?->id : null,
            ])
            ->create();
    }
}
