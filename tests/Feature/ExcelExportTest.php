<?php

namespace Tests\Feature;

use App\Filament\Pages\ExpensesReport;
use App\Filament\Pages\MonthlyReport;
use App\Filament\Pages\PatientCostsReport;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Models\Expense;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        Expense::factory()->count(3)->forPatient()->create(['spent_on' => now()]);
    }

    public function test_report_pages_download_excel_files(): void
    {
        $date = now()->format('Y-m-d');

        $component = Livewire::test(ExpensesReport::class)
            ->callAction('exportExcel')
            ->assertFileDownloaded("تقرير-المصاريف-{$date}.xlsx");

        $content = base64_decode(data_get($component->effects, 'download.content'));
        $this->assertStringStartsWith('PK', $content, 'The download should be a real XLSX (zip) file.');

        Livewire::test(MonthlyReport::class)
            ->callAction('exportExcel')
            ->assertFileDownloaded("المقارنة-الشهرية-{$date}.xlsx");

        Livewire::test(PatientCostsReport::class)
            ->callAction('exportExcel')
            ->assertFileDownloaded("حسابات-المرضى-{$date}.xlsx");
    }

    public function test_list_pages_download_excel_files(): void
    {
        $date = now()->format('Y-m-d');

        Livewire::test(ListExpenses::class)
            ->callAction(TestAction::make('exportExcel')->table())
            ->assertFileDownloaded("المصاريف-{$date}.xlsx");

        Livewire::test(ListPatients::class)
            ->callAction(TestAction::make('exportExcel')->table())
            ->assertFileDownloaded("المرضى-{$date}.xlsx");
    }
}
