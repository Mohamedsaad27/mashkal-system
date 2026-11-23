<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * The initial expense categories the facility starts with.
     *
     * @var list<string>
     */
    public const INITIAL_CATEGORIES = [
        'أكل وتموين',
        'أدوية',
        'مرتبات',
        'إيجار',
        'كهرباء ومياه',
        'صيانة',
        'نظافة',
        'مواصلات',
        'نثريات',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::INITIAL_CATEGORIES as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
