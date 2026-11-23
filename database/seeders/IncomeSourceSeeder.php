<?php

namespace Database\Seeders;

use App\Models\IncomeSource;
use Illuminate\Database\Seeder;

class IncomeSourceSeeder extends Seeder
{
    /**
     * The initial income sources the facility starts with.
     *
     * @var list<string>
     */
    public const INITIAL_SOURCES = [
        'اشتراك شهري',
        'رسوم دخول',
        'تبرعات',
        'أخرى',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::INITIAL_SOURCES as $name) {
            IncomeSource::firstOrCreate(['name' => $name]);
        }
    }
}
