<?php

namespace Tests\Feature;

use App\Filament\Resources\Expenses\Widgets\ExpenseStats;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_kpis_summarise_all_non_trashed_expenses(): void
    {
        $rent = ExpenseCategory::factory()->create(['name' => 'إيجار']);
        $food = ExpenseCategory::factory()->create(['name' => 'أكل']);

        Expense::factory()->for($rent, 'category')->create(['amount' => 600]);
        Expense::factory()->for($food, 'category')->create(['amount' => 100]);
        Expense::factory()->for($food, 'category')->create(['amount' => 200]);
        Expense::factory()->for($food, 'category')->create(['amount' => 5000])->delete();

        Livewire::test(ExpenseStats::class)
            ->assertSee('900.00')
            ->assertSee('300.00')
            ->assertSee('إيجار');
    }

    public function test_kpis_follow_the_table_filters(): void
    {
        $rent = ExpenseCategory::factory()->create(['name' => 'إيجار']);
        $food = ExpenseCategory::factory()->create(['name' => 'أكل']);

        Expense::factory()->for($rent, 'category')->create(['amount' => 600]);
        Expense::factory()->for($food, 'category')->create(['amount' => 100]);
        Expense::factory()->for($food, 'category')->create(['amount' => 200]);

        Livewire::test(ExpenseStats::class, [
            'tableFilters' => ['expense_category_id' => ['values' => [$food->id]]],
        ])
            ->assertSee('300.00')
            ->assertSee('150.00')
            ->assertDontSee('900.00');
    }
}
