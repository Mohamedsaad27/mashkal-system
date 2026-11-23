<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Models\Patient;
use App\Support\XlsxDownload;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('admitted_on', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المريض')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('رقم التليفون')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('admitted_on')
                    ->label('تاريخ الدخول')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('arrival_supervisor')
                    ->label('مشرف الوصول')
                    ->searchable(),
                TextColumn::make('discharged_on')
                    ->label('تاريخ الخروج')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_resident')
                    ->label('مقيم')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('resident')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('مقيم')
                    ->falseLabel('خرج')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('discharged_on'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('discharged_on'),
                    ),
                TrashedFilter::make()
                    ->label('سلة المحذوفات'),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('تصدير Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (HasTable $livewire): StreamedResponse => self::export($livewire)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Export the patients currently shown in the table (with filters applied).
     */
    private static function export(HasTable $livewire): StreamedResponse
    {
        $patients = $livewire->getFilteredSortedTableQuery()->get();

        return XlsxDownload::make(
            'المرضى-'.now()->format('Y-m-d'),
            ['اسم المريض', 'رقم التليفون', 'تاريخ الدخول', 'مشرف الوصول', 'تاريخ الخروج', 'الحالة'],
            $patients->map(fn (Patient $patient): array => [
                $patient->name,
                $patient->phone,
                $patient->admitted_on->format('Y-m-d'),
                $patient->arrival_supervisor,
                $patient->discharged_on?->format('Y-m-d'),
                $patient->is_resident ? 'مقيم' : 'خرج',
            ])->all(),
        );
    }
}
