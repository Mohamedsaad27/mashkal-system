<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

/**
 * Demo patients for local development only.
 */
class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Patient::factory()->count(12)->create();
        Patient::factory()->count(6)->discharged()->create();
    }
}
