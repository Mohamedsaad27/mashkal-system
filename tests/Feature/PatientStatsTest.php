<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Widgets\PatientStats;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->travelTo('2026-11-20');
    }

    public function test_kpis_count_residents_discharged_and_new_admissions(): void
    {
        Patient::factory()->create(['admitted_on' => '2026-11-05']);
        Patient::factory()->create(['admitted_on' => '2026-09-01']);
        Patient::factory()->create(['admitted_on' => '2026-08-01', 'discharged_on' => '2026-10-01']);
        Patient::factory()->create(['admitted_on' => '2026-11-02'])->delete();

        $stats = $this->statValues(Livewire::test(PatientStats::class)->instance());

        $this->assertSame([
            'إجمالي المرضى' => '3',
            'مقيم حاليًا' => '2',
            'خرجوا' => '1',
            'دخلوا هذا الشهر' => '1',
        ], $stats);
    }

    public function test_kpis_follow_the_table_filters(): void
    {
        Patient::factory()->count(2)->create(['admitted_on' => '2026-11-05']);
        Patient::factory()->create(['admitted_on' => '2026-08-01', 'discharged_on' => '2026-10-01']);

        $component = Livewire::test(PatientStats::class, [
            'tableFilters' => ['resident' => ['value' => '0']],
        ])->instance();

        $stats = $this->statValues($component);

        $this->assertSame('1', $stats['إجمالي المرضى']);
        $this->assertSame('0', $stats['مقيم حاليًا']);
    }

    /**
     * @return array<string, string>
     */
    private function statValues(PatientStats $widget): array
    {
        $method = new \ReflectionMethod($widget, 'getStats');

        return collect($method->invoke($widget))
            ->mapWithKeys(fn ($stat): array => [(string) $stat->getLabel() => (string) $stat->getValue()])
            ->all();
    }
}
