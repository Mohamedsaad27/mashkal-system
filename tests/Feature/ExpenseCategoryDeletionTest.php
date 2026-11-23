<?php

namespace Tests\Feature;

use App\Filament\Resources\ExpenseCategories\Pages\ManageExpenseCategories;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseCategoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = ExpenseCategory::factory()->create();

        Livewire::test(ManageExpenseCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($category));

        $this->assertModelMissing($category);
    }

    public function test_category_with_expenses_cannot_be_deleted(): void
    {
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->for($category, 'category')->create();

        Livewire::test(ManageExpenseCategories::class)
            ->assertActionDisabled(TestAction::make(DeleteAction::class)->table($category));

        $this->assertModelExists($category);
    }

    public function test_category_with_only_trashed_expenses_cannot_be_deleted(): void
    {
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->for($category, 'category')->create()->delete();

        $this->assertFalse($category->isDeletable());
    }
}
