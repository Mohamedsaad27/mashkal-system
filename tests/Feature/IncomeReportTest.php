<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Patient;
use App\Support\ExpenseReport;
use App\Support\IncomeReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_totals_by_source_exclude_trashed_income_and_respect_the_period(): void
    {
        $monthly = IncomeSource::factory()->create(['name' => 'اشتراك شهري']);
        $donation = IncomeSource::factory()->create(['name' => 'تبرعات']);

        Income::factory()->for($monthly, 'source')->create(['amount' => 3000, 'received_on' => '2026-11-03']);
        Income::factory()->for($monthly, 'source')->create(['amount' => 3000, 'received_on' => '2026-11-04']);
        Income::factory()->for($donation, 'source')->create(['amount' => 4000, 'received_on' => '2026-11-10']);
        Income::factory()->for($donation, 'source')->create(['amount' => 999, 'received_on' => '2026-10-31']);
        Income::factory()->for($monthly, 'source')->create(['amount' => 9999, 'received_on' => '2026-11-05'])->delete();

        $totals = IncomeReport::totalsBySource('2026-11-01', '2026-11-30');

        $this->assertSame(6000.0, $totals[$monthly->id]['total']);
        $this->assertSame(2, $totals[$monthly->id]['count']);
        $this->assertSame(60.0, $totals[$monthly->id]['percentage']);
        $this->assertSame(4000.0, $totals[$donation->id]['total']);
    }

    public function test_total_for_month_only_counts_that_month(): void
    {
        Income::factory()->create(['amount' => 500, 'received_on' => '2026-11-01']);
        Income::factory()->create(['amount' => 700, 'received_on' => '2026-11-30']);
        Income::factory()->create(['amount' => 900, 'received_on' => '2026-12-01']);

        $this->assertSame(1200.0, IncomeReport::totalForMonth(CarbonImmutable::parse('2026-11-15')));
    }

    public function test_patient_accounts_show_cost_and_payments_separately(): void
    {
        $patient = Patient::factory()->create();
        Expense::factory()->forPatient($patient)->create(['amount' => 400]);
        Income::factory()->forPatient($patient)->create(['amount' => 3000]);
        Income::factory()->forPatient($patient)->create(['amount' => 1500]);
        Income::factory()->forPatient($patient)->create(['amount' => 777])->delete();

        $row = ExpenseReport::patientCostsQuery()->find($patient->id);

        $this->assertEquals(400, $row->expenses_sum_amount);
        $this->assertEquals(4500, $row->incomes_sum_amount);
    }
}
