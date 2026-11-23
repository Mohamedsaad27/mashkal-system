<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use Illuminate\Database\Seeder;

/**
 * Demo expenses for local development only.
 */
class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ExpenseCategory::all();
        $patients = Patient::all();

        Expense::factory()
            ->count(150)
            ->sequence(fn (): array => [
                'expense_category_id' => $categories->random()->id,
                'patient_id' => fake()->boolean(25) ? $patients->random()?->id : null,
            ])
            ->create();
    }
}
