<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * On production create the single user with `php artisan make:filament-user`.
     */
    public function run(): void
    {
        $this->call([ExpenseCategorySeeder::class, IncomeSourceSeeder::class]);

        if (app()->isLocal()) {
            User::firstOrCreate(
                ['email' => 'admin@mashkal.test'],
                ['name' => 'المدير', 'password' => 'password'],
            );
        }
    }
}
