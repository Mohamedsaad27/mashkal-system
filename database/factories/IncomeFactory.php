<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 500, 15000),
            'income_source_id' => IncomeSource::factory(),
            'patient_id' => null,
            'received_on' => fake()->dateTimeBetween('-12 months', 'now'),
        ];
    }

    /**
     * Income received from a specific patient.
     */
    public function forPatient(?Patient $patient = null): static
    {
        return $this->state(fn (): array => [
            'patient_id' => $patient ?? Patient::factory(),
        ]);
    }
}
