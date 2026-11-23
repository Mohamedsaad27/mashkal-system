<?php

namespace App\Filament\Pages;

use App\Models\ExpenseCategory;
use App\Support\ExpenseReport;
use App\Support\Money;
use App\Support\XlsxDownload;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Expense totals for a period, broken down by category.
 */
class ExpensesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'تقرير المصاريف';

    protected static ?string $title = 'تقرير المصاريف حسب السبب';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters): array => $this->totals($filters)->all())
            ->heading(fn (): string => 'إجمالي الفترة: '.self::money($this->totals($this->tableFilters)->sum('total')))
            ->columns([
                TextColumn::make('category')
                    ->label('سبب الصرف')
                    ->weight('bold'),
                TextColumn::make('count')
                    ->label('عدد المصاريف'),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state)),
                TextColumn::make('percentage')
                    ->label('النسبة من الإجمالي')
                    ->suffix('%'),
            ])
            ->filters([
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                SelectFilter::make('categories')
                    ->label('سبب الصرف')
                    ->multiple()
                    ->options(fn (): array => ExpenseCategory::query()->orderBy('name')->pluck('name', 'id')->all()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->emptyStateHeading('لا توجد مصاريف في هذه الفترة')
            ->paginated(false);
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
        $totals = $this->totals($this->tableFilters);
        $period = $this->tableFilters['period'] ?? [];

        $rows = $totals->map(fn (array $row): array => [
            $row['category'],
            $row['count'],
            $row['total'],
            $row['percentage'],
        ])->values()->all();

        $rows[] = ['الإجمالي', $totals->sum('count'), $totals->sum('total'), 100];
        $rows[] = [];
        $rows[] = ['الفترة', ($period['from'] ?? 'البداية').' إلى '.($period['until'] ?? 'اليوم')];

        return XlsxDownload::make(
            'تقرير-المصاريف-'.now()->format('Y-m-d'),
            ['سبب الصرف', 'عدد المصاريف', 'الإجمالي (ج.م)', 'النسبة %'],
            $rows,
        );
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return Collection<int, array{category: string, count: int, total: float, percentage: float}>
     */
    private function totals(?array $filters): Collection
    {
        return ExpenseReport::totalsByCategory(
            $filters['period']['from'] ?? null,
            $filters['period']['until'] ?? null,
            array_map('intval', $filters['categories']['values'] ?? []),
        );
    }

    private static function money(float $amount): string
    {
        return Money::format($amount);
    }
}
