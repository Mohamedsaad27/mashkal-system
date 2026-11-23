<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 50, 5000),
            'expense_category_id' => ExpenseCategory::factory(),
            'patient_id' => null,
            'spent_on' => fake()->dateTimeBetween('-12 months', 'now'),
        ];
    }

    /**
     * An expense spent on a specific patient.
     */
    public function forPatient(?Patient $patient = null): static
    {
        return $this->state(fn (): array => [
            'patient_id' => $patient ?? Patient::factory(),
        ]);
    }
}
