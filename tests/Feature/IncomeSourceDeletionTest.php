<?php

namespace Tests\Feature;

use App\Filament\Resources\IncomeSources\Pages\ManageIncomeSources;
use App\Models\Income;
use App\Models\IncomeSource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IncomeSourceDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_unused_source_can_be_deleted(): void
    {
        $source = IncomeSource::factory()->create();

        Livewire::test(ManageIncomeSources::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($source));

        $this->assertModelMissing($source);
    }

    public function test_source_with_income_cannot_be_deleted(): void
    {
        $source = IncomeSource::factory()->create();
        Income::factory()->for($source, 'source')->create();

        Livewire::test(ManageIncomeSources::class)
            ->assertActionDisabled(TestAction::make(DeleteAction::class)->table($source));

        $this->assertModelExists($source);
    }

    public function test_source_with_only_trashed_income_cannot_be_deleted(): void
    {
        $source = IncomeSource::factory()->create();
        Income::factory()->for($source, 'source')->create()->delete();

        $this->assertFalse($source->isDeletable());
    }
}
