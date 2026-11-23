<?php

namespace App\Filament\Pages;

use App\Models\Patient;
use App\Support\ExpenseReport;
use App\Support\Money;
use App\Support\XlsxDownload;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Each patient's total cost (linked expenses) and total payments (linked income).
 */
class PatientCostsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'حسابات المرضى';

    protected static ?string $title = 'حسابات المرضى: التكلفة والمدفوعات';

    public function table(Table $table): Table
    {
        return $table
            ->query(ExpenseReport::patientCostsQuery())
            ->defaultSort('expenses_sum_amount', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المريض')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Patient $record): ?string => $record->trashed() ? 'في سلة المحذوفات' : null),
                TextColumn::make('admitted_on')
                    ->label('تاريخ الدخول')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('discharged_on')
                    ->label('تاريخ الخروج')
                    ->date('d/m/Y')
                    ->placeholder('مقيم')
                    ->sortable(),
                TextColumn::make('expenses_count')
                    ->label('عدد المصاريف')
                    ->sortable(),
                TextColumn::make('expenses_sum_amount')
                    ->label('إجمالي التكلفة')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('incomes_sum_amount')
                    ->label('إجمالي المدفوعات')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state))
                    ->color('success')
                    ->placeholder('—')
                    ->sortable(),
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
                TernaryFilter::make('has_expenses')
                    ->label('عليه مصاريف')
                    ->placeholder('الكل')
                    ->trueLabel('نعم')
                    ->falseLabel('لا')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('expenses'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('expenses'),
                    ),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('تصدير Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    private function export(): StreamedResponse
    {
        $patients = $this->getFilteredSortedTableQuery()->get();

        return XlsxDownload::make(
            'حسابات-المرضى-'.now()->format('Y-m-d'),
            ['اسم المريض', 'تاريخ الدخول', 'تاريخ الخروج', 'عدد المصاريف', 'إجمالي التكلفة (ج.م)', 'إجمالي المدفوعات (ج.م)'],
            $patients->map(fn (Patient $patient): array => [
                $patient->name,
                $patient->admitted_on->format('Y-m-d'),
                $patient->discharged_on?->format('Y-m-d') ?? 'مقيم',
                (int) $patient->expenses_count,
                (float) ($patient->expenses_sum_amount ?? 0),
                (float) ($patient->incomes_sum_amount ?? 0),
            ])->all(),
        );
    }
}
