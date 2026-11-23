<?php

namespace Tests\Feature;

use App\Filament\Resources\Expenses\ExpensePasswordGate;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_expense_can_be_created_without_a_patient(): void
    {
        $category = ExpenseCategory::factory()->create();

        Livewire::test(CreateExpense::class)
            ->fillForm([
                'amount' => 250.5,
                'expense_category_id' => $category->id,
                'spent_on' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $expense = Expense::sole();
        $this->assertSame('250.50', $expense->amount);
        $this->assertNull($expense->patient_id);
    }

    public function test_amount_must_be_positive(): void
    {
        Livewire::test(CreateExpense::class)
            ->fillForm([
                'amount' => 0,
                'expense_category_id' => ExpenseCategory::factory()->create()->id,
                'spent_on' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['amount' => 'min']);
    }

    public function test_expense_linked_to_a_trashed_patient_can_still_be_edited(): void
    {
        $patient = Patient::factory()->create();
        $expense = Expense::factory()->forPatient($patient)->create(['amount' => 100]);
        $patient->delete();

        ExpensePasswordGate::grantEditAccess($expense);

        Livewire::test(EditExpense::class, ['record' => $expense->getRouteKey()])
            ->assertSchemaStateSet(['patient_id' => $patient->id])
            ->fillForm(['amount' => 175])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('175.00', $expense->refresh()->amount);
        $this->assertSame($patient->id, $expense->patient_id);
    }
}
