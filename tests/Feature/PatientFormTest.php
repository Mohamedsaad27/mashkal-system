<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_patient_can_be_created(): void
    {
        Livewire::test(CreatePatient::class)
            ->fillForm([
                'name' => 'أحمد محمد',
                'phone' => '01012345678',
                'admitted_on' => '2026-11-01',
                'arrival_supervisor' => 'محمود',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $patient = Patient::sole();
        $this->assertSame('01012345678', $patient->phone);
        $this->assertTrue($patient->is_resident);
    }

    public function test_phone_must_be_an_egyptian_mobile_number(): void
    {
        Livewire::test(CreatePatient::class)
            ->fillForm([
                'name' => 'أحمد',
                'phone' => '01312345678',
                'admitted_on' => '2026-11-01',
                'arrival_supervisor' => 'محمود',
            ])
            ->call('create')
            ->assertHasFormErrors(['phone' => 'regex']);
    }

    public function test_discharge_date_cannot_be_before_admission(): void
    {
        Livewire::test(CreatePatient::class)
            ->fillForm([
                'name' => 'أحمد',
                'phone' => '01012345678',
                'admitted_on' => '2026-11-10',
                'arrival_supervisor' => 'محمود',
                'discharged_on' => '2026-11-01',
            ])
            ->call('create')
            ->assertHasFormErrors(['discharged_on' => 'after_or_equal']);
    }

    public function test_duplicate_phone_numbers_are_allowed(): void
    {
        Patient::factory()->create(['phone' => '01012345678']);

        Livewire::test(CreatePatient::class)
            ->fillForm([
                'name' => 'شقيق المريض',
                'phone' => '01012345678',
                'admitted_on' => '2026-11-01',
                'arrival_supervisor' => 'محمود',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, Patient::count());
    }
}
