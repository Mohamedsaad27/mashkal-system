<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use App\Support\ExpenseReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_totals_by_category_exclude_trashed_expenses_and_respect_the_period(): void
    {
        $food = ExpenseCategory::factory()->create(['name' => 'أكل']);
        $rent = ExpenseCategory::factory()->create(['name' => 'إيجار']);

        Expense::factory()->for($food, 'category')->create(['amount' => 100, 'spent_on' => '2026-11-05']);
        Expense::factory()->for($food, 'category')->create(['amount' => 50, 'spent_on' => '2026-11-10']);
        Expense::factory()->for($rent, 'category')->create(['amount' => 850, 'spent_on' => '2026-11-01']);
        Expense::factory()->for($rent, 'category')->create(['amount' => 999, 'spent_on' => '2026-10-31']);
        Expense::factory()->for($food, 'category')->create(['amount' => 500, 'spent_on' => '2026-11-06'])->delete();

        $totals = ExpenseReport::totalsByCategory('2026-11-01', '2026-11-30');

        $this->assertSame(150.0, $totals[$food->id]['total']);
        $this->assertSame(2, $totals[$food->id]['count']);
        $this->assertSame(850.0, $totals[$rent->id]['total']);
        $this->assertSame(85.0, $totals[$rent->id]['percentage']);
    }

    public function test_totals_by_category_can_be_limited_to_selected_categories(): void
    {
        $food = ExpenseCategory::factory()->create();
        $rent = ExpenseCategory::factory()->create();

        Expense::factory()->for($food, 'category')->create(['amount' => 100]);
        Expense::factory()->for($rent, 'category')->create(['amount' => 300]);

        $totals = ExpenseReport::totalsByCategory(null, null, [$rent->id]);

        $this->assertSame([$rent->id], $totals->keys()->all());
    }

    public function test_monthly_totals_cover_twelve_months_including_empty_ones(): void
    {
        $this->travelTo('2026-11-15');

        Expense::factory()->create(['amount' => 200, 'spent_on' => '2026-11-02']);
        Expense::factory()->create(['amount' => 300, 'spent_on' => '2026-09-20']);
        Expense::factory()->create(['amount' => 999, 'spent_on' => '2025-11-30']);

        $months = ExpenseReport::monthlyTotals();

        $this->assertCount(12, $months);
        $this->assertSame('2025-12', $months[0]['month']);
        $this->assertSame(200.0, $months[11]['total']);
        $this->assertSame(0.0, $months[10]['total']);
        $this->assertSame(300.0, $months[9]['total']);
    }

    public function test_patient_costs_include_trashed_patients_but_not_trashed_expenses(): void
    {
        $patient = Patient::factory()->create();
        Expense::factory()->forPatient($patient)->create(['amount' => 120]);
        Expense::factory()->forPatient($patient)->create(['amount' => 80]);
        Expense::factory()->forPatient($patient)->create(['amount' => 1000])->delete();
        $patient->delete();

        $row = ExpenseReport::patientCostsQuery()->find($patient->id);

        $this->assertNotNull($row);
        $this->assertEquals(200, $row->expenses_sum_amount);
        $this->assertSame(2, $row->expenses_count);
    }
}
