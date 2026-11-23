<?php

namespace Tests\Feature;

use App\Filament\Pages\IncomeReportPage;
use App\Filament\Resources\Incomes\Pages\ListIncomes;
use App\Filament\Resources\Incomes\Widgets\IncomeStats;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IncomeStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_kpis_follow_the_table_filters(): void
    {
        $monthly = IncomeSource::factory()->create(['name' => 'اشتراك شهري']);
        $donation = IncomeSource::factory()->create(['name' => 'تبرعات']);

        Income::factory()->for($monthly, 'source')->create(['amount' => 1000]);
        Income::factory()->for($monthly, 'source')->create(['amount' => 2000]);
        Income::factory()->for($donation, 'source')->create(['amount' => 9000]);
        Income::factory()->for($monthly, 'source')->create(['amount' => 50000])->delete();

        Livewire::test(IncomeStats::class)
            ->assertSee('12,000.00')
            ->assertSee('تبرعات');

        Livewire::test(IncomeStats::class, [
            'tableFilters' => ['income_source_id' => ['values' => [$monthly->id]]],
        ])
            ->assertSee('3,000.00')
            ->assertSee('1,500.00')
            ->assertDontSee('12,000.00');
    }

    public function test_income_pages_download_excel_files(): void
    {
        Income::factory()->count(2)->create(['received_on' => now()]);
        $date = now()->format('Y-m-d');

        Livewire::test(ListIncomes::class)
            ->callAction(TestAction::make('exportExcel')->table())
            ->assertFileDownloaded("الدخل-{$date}.xlsx");

        Livewire::test(IncomeReportPage::class)
            ->callAction('exportExcel')
            ->assertFileDownloaded("تقرير-الدخل-{$date}.xlsx");
    }
}
