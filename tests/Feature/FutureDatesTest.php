<?php

namespace Tests\Feature;

use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Incomes\Pages\CreateIncome;
use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FutureDatesTest extends TestCase
{
    use RefreshDatabase;

    private string $nextMonth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->nextMonth = now()->addMonth()->toDateString();
    }

    public function test_patient_admission_date_can_be_in_the_future(): void
    {
        Livewire::test(CreatePatient::class)
            ->fillForm([
                'name' => 'أحمد',
                'phone' => '01012345678',
                'admitted_on' => $this->nextMonth,
                'arrival_supervisor' => 'محمود',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($this->nextMonth, Patient::sole()->admitted_on->toDateString());
    }

    public function test_expense_date_can_be_in_the_future(): void
    {
        Livewire::test(CreateExpense::class)
            ->fillForm([
                'amount' => 100,
                'expense_category_id' => ExpenseCategory::factory()->create()->id,
                'spent_on' => $this->nextMonth,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($this->nextMonth, Expense::sole()->spent_on->toDateString());
    }

    public function test_income_date_can_be_in_the_future(): void
    {
        Livewire::test(CreateIncome::class)
            ->fillForm([
                'amount' => 100,
                'income_source_id' => IncomeSource::factory()->create()->id,
                'received_on' => $this->nextMonth,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($this->nextMonth, Income::sole()->received_on->toDateString());
    }
}
