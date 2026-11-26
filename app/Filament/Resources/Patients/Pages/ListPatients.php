<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Filament\Resources\Patients\Widgets\PatientStats;
use App\Support\PatientImporter;
use App\Support\XlsxDownload;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPatients extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ActionGroup::make([
                Action::make('importExcel')
                    ->label('استيراد من Excel')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->modalHeading('استيراد المرضى من Excel')
                    ->modalDescription('الصف الأول في الملف لازم يكون عناوين الأعمدة: الاسم - رقم التليفون - تاريخ الدخول - مشرف الوصول. لو فيه أي صف فيه خطأ، مش هيتم استيراد أي حاجة لحد ما تصلحه.')
                    ->modalSubmitActionLabel('استيراد')
                    ->schema([
                        FileUpload::make('file')
                            ->label('ملف Excel')
                            ->required()
                            ->disk('local')
                            ->directory('patient-imports')
                            ->preserveFilenames(false)
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'text/plain',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(5120),
                    ])
                    ->action(function (array $data): void {
                        $this->importPatients($data['file']);
                    }),
                Action::make('downloadTemplate')
                    ->label('تحميل نموذج الاستيراد')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->action(fn (): StreamedResponse => XlsxDownload::make(
                        'نموذج-استيراد-المرضى',
                        array_keys(PatientImporter::COLUMNS),
                        [['أحمد محمد', '01012345678', now()->format('d/m/Y'), 'محمود علي']],
                    )),
            ])
                ->label('استيراد')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->button()
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PatientStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    private function importPatients(string $storedPath): void
    {
        $disk = Storage::disk('local');

        try {
            $result = PatientImporter::import($disk->path($storedPath), pathinfo($storedPath, PATHINFO_EXTENSION));
        } finally {
            $disk->delete($storedPath);
        }

        if ($result['errors'] !== []) {
            $shownErrors = array_slice($result['errors'], 0, 10);
            $remaining = count($result['errors']) - count($shownErrors);

            Notification::make()
                ->title('لم يتم استيراد أي مريض')
                ->body(implode("\n", $shownErrors).($remaining > 0 ? "\nو{$remaining} أخطاء أخرى." : ''))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title("تم استيراد {$result['imported']} مريض بنجاح")
            ->success()
            ->send();
    }
}
