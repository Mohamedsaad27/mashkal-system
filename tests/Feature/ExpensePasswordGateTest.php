<?php

namespace Tests\Feature;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Models\Expense;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpensePasswordGateTest extends TestCase
{
    use RefreshDatabase;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['password' => 'secret-123']));
        $this->expense = Expense::factory()->create();
    }

    public function test_wrong_password_does_not_open_the_edit_page(): void
    {
        Livewire::test(ListExpenses::class)
            ->callAction(TestAction::make('edit')->table($this->expense), ['password' => 'wrong'])
            ->assertHasFormErrors(['password' => 'current_password'])
            ->assertNoRedirect();
    }

    public function test_correct_password_opens_the_edit_page_once(): void
    {
        Livewire::test(ListExpenses::class)
            ->callAction(TestAction::make('edit')->table($this->expense), ['password' => 'secret-123'])
            ->assertHasNoFormErrors()
            ->assertRedirect(ExpenseResource::getUrl('edit', ['record' => $this->expense]));

        Livewire::test(EditExpense::class, ['record' => $this->expense->getRouteKey()])
            ->assertNoRedirect();

        Livewire::test(EditExpense::class, ['record' => $this->expense->getRouteKey()])
            ->assertRedirect(ExpenseResource::getUrl('index'));
    }

    public function test_opening_the_edit_url_directly_redirects_back_to_the_list(): void
    {
        Livewire::test(EditExpense::class, ['record' => $this->expense->getRouteKey()])
            ->assertRedirect(ExpenseResource::getUrl('index'));
    }

    public function test_an_expired_pass_does_not_open_the_edit_page(): void
    {
        Livewire::test(ListExpenses::class)
            ->callAction(TestAction::make('edit')->table($this->expense), ['password' => 'secret-123']);

        $this->travel(5)->minutes();

        Livewire::test(EditExpense::class, ['record' => $this->expense->getRouteKey()])
            ->assertRedirect(ExpenseResource::getUrl('index'));
    }

    public function test_delete_requires_the_password(): void
    {
        Livewire::test(ListExpenses::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($this->expense), ['password' => 'wrong'])
            ->assertHasFormErrors(['password' => 'current_password']);

        $this->assertNotSoftDeleted($this->expense);

        Livewire::test(ListExpenses::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($this->expense), ['password' => 'secret-123'])
            ->assertHasNoFormErrors();

        $this->assertSoftDeleted($this->expense);
    }

    public function test_bulk_delete_requires_the_password(): void
    {
        $other = Expense::factory()->create();

        Livewire::test(ListExpenses::class)
            ->selectTableRecords([$this->expense->getKey(), $other->getKey()])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk(), ['password' => 'wrong'])
            ->assertHasFormErrors(['password' => 'current_password']);

        $this->assertNotSoftDeleted($this->expense);

        Livewire::test(ListExpenses::class)
            ->selectTableRecords([$this->expense->getKey(), $other->getKey()])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk(), ['password' => 'secret-123']);

        $this->assertSoftDeleted($this->expense);
        $this->assertSoftDeleted($other);
    }
}
