<?php

namespace Tests\Feature;

use App\Filament\Resources\Incomes\Pages\CreateIncome;
use App\Filament\Resources\Incomes\Pages\EditIncome;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IncomeFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_income_can_be_created_for_a_patient(): void
    {
        $source = IncomeSource::factory()->create();
        $patient = Patient::factory()->create();

        Livewire::test(CreateIncome::class)
            ->fillForm([
                'amount' => 3000,
                'income_source_id' => $source->id,
                'received_on' => now()->toDateString(),
                'patient_id' => $patient->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $income = Income::sole();
        $this->assertSame('3000.00', $income->amount);
        $this->assertSame($patient->id, $income->patient_id);
    }

    public function test_amount_must_be_positive_and_source_is_required(): void
    {
        Livewire::test(CreateIncome::class)
            ->fillForm([
                'amount' => 0,
                'received_on' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['amount' => 'min', 'income_source_id' => 'required']);
    }

    public function test_income_linked_to_a_trashed_patient_can_still_be_edited(): void
    {
        $patient = Patient::factory()->create();
        $income = Income::factory()->forPatient($patient)->create(['amount' => 100]);
        $patient->delete();

        Livewire::test(EditIncome::class, ['record' => $income->getRouteKey()])
            ->assertSchemaStateSet(['patient_id' => $patient->id])
            ->fillForm(['amount' => 250])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('250.00', $income->refresh()->amount);
    }
}
