<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '01'.fake()->randomElement(['0', '1', '2', '5']).fake()->numerify('########'),
            'admitted_on' => fake()->dateTimeBetween('-6 months', 'now'),
            'arrival_supervisor' => fake()->name(),
            'discharged_on' => null,
        ];
    }

    /**
     * A patient who has left the facility.
     */
    public function discharged(): static
    {
        return $this->state(fn (array $attributes): array => [
            'discharged_on' => fake()->dateTimeBetween($attributes['admitted_on'], 'now'),
        ]);
    }
}
