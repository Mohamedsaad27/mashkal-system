<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Models\Patient;
use App\Models\User;
use App\Support\PatientImporter;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class PatientImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADINGS = ['الاسم', 'رقم التليفون', 'تاريخ الدخول', 'مشرف الوصول'];

    public function test_valid_rows_are_imported(): void
    {
        $path = $this->makeXlsx([
            self::HEADINGS,
            ['أحمد محمد', '01012345678', '05/11/2026', 'محمود'],
            ['سعيد علي', 1112345678, new DateTimeImmutable('2026-10-20'), 'كريم'],
            [null, null, null, null],
            ['منى حسن', '٠١٥١٢٣٤٥٦٧٨', '2026-09-01', 'محمود'],
        ]);

        $result = PatientImporter::import($path, 'xlsx');

        $this->assertSame(['imported' => 3, 'errors' => []], $result);
        $imported = Patient::query()->orderBy('id')->get()
            ->map(fn (Patient $patient): array => [$patient->name, $patient->phone, $patient->admitted_on->toDateString(), $patient->arrival_supervisor])
            ->all();

        $this->assertSame([
            ['أحمد محمد', '01012345678', '2026-11-05', 'محمود'],
            ['سعيد علي', '01112345678', '2026-10-20', 'كريم'],
            ['منى حسن', '01512345678', '2026-09-01', 'محمود'],
        ], $imported);
    }

    public function test_columns_can_be_in_any_order(): void
    {
        $path = $this->makeXlsx([
            ['مشرف الوصول', 'تاريخ الدخول', 'الاسم', 'رقم التليفون'],
            ['محمود', '01/11/2026', 'أحمد', '01012345678'],
        ]);

        PatientImporter::import($path, 'xlsx');

        $this->assertDatabaseHas('patients', ['name' => 'أحمد', 'arrival_supervisor' => 'محمود', 'phone' => '01012345678']);
    }

    public function test_nothing_is_imported_when_any_row_is_invalid(): void
    {
        $path = $this->makeXlsx([
            self::HEADINGS,
            ['أحمد', '01012345678', '05/11/2026', 'محمود'],
            ['سعيد', '0131234567', '05/11/2026', 'كريم'],
            ['منى', '01012345678', '32/13/2026', ''],
        ]);

        $result = PatientImporter::import($path, 'xlsx');

        $this->assertSame(0, $result['imported']);
        $this->assertCount(2, $result['errors']);
        $this->assertStringStartsWith('صف 3:', $result['errors'][0]);
        $this->assertStringContainsString('رقم التليفون', $result['errors'][0]);
        $this->assertStringStartsWith('صف 4:', $result['errors'][1]);
        $this->assertSame(0, Patient::count());
    }

    public function test_missing_heading_is_reported(): void
    {
        $path = $this->makeXlsx([
            ['الاسم', 'رقم التليفون', 'تاريخ الدخول'],
            ['أحمد', '01012345678', '05/11/2026'],
        ]);

        $result = PatientImporter::import($path, 'xlsx');

        $this->assertSame(['العمود "مشرف الوصول" غير موجود في الصف الأول من الملف.'], $result['errors']);
    }

    public function test_csv_files_are_supported(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'patients').'.csv';
        file_put_contents($path, "الاسم,رقم التليفون,تاريخ الدخول,مشرف الوصول\nأحمد,01012345678,05/11/2026,محمود\n");

        $result = PatientImporter::import($path, 'csv');

        $this->assertSame(1, $result['imported']);
    }

    public function test_import_action_on_patients_page(): void
    {
        $this->actingAs(User::factory()->create());

        $path = $this->makeXlsx([
            self::HEADINGS,
            ['أحمد', '01012345678', '05/11/2026', 'محمود'],
        ]);
        $file = UploadedFile::fake()->createWithContent('patients.xlsx', file_get_contents($path));

        Livewire::test(ListPatients::class)
            ->callAction('importExcel', ['file' => $file])
            ->assertHasNoFormErrors()
            ->assertNotified('تم استيراد 1 مريض بنجاح');

        $this->assertSame(1, Patient::count());
    }

    public function test_template_can_be_downloaded(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListPatients::class)
            ->callAction('downloadTemplate')
            ->assertFileDownloaded('نموذج-استيراد-المرضى.xlsx');
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function makeXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'patients').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }
}
