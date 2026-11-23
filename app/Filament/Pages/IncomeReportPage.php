<?php

namespace App\Filament\Pages;

use App\Models\IncomeSource;
use App\Support\IncomeReport;
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
 * Income totals for a period, broken down by source.
 */
class IncomeReportPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static ?string $slug = 'income-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'تقرير الدخل';

    protected static ?string $title = 'تقرير الدخل حسب المصدر';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?array $filters): array => $this->totals($filters)->all())
            ->heading(fn (): string => 'إجمالي الفترة: '.Money::format($this->totals($this->tableFilters)->sum('total')))
            ->columns([
                TextColumn::make('source')
                    ->label('مصدر الدخل')
                    ->weight('bold'),
                TextColumn::make('count')
                    ->label('عدد مرات الدخل'),
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
                SelectFilter::make('sources')
                    ->label('مصدر الدخل')
                    ->multiple()
                    ->options(fn (): array => IncomeSource::query()->orderBy('name')->pluck('name', 'id')->all()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters(false)
            ->emptyStateHeading('لا يوجد دخل في هذه الفترة')
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
            $row['source'],
            $row['count'],
            $row['total'],
            $row['percentage'],
        ])->values()->all();

        $rows[] = ['الإجمالي', $totals->sum('count'), $totals->sum('total'), 100];
        $rows[] = [];
        $rows[] = ['الفترة', ($period['from'] ?? 'البداية').' إلى '.($period['until'] ?? 'اليوم')];

        return XlsxDownload::make(
            'تقرير-الدخل-'.now()->format('Y-m-d'),
            ['مصدر الدخل', 'عدد مرات الدخل', 'الإجمالي (ج.م)', 'النسبة %'],
            $rows,
        );
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return Collection<int, array{source: string, count: int, total: float, percentage: float}>
     */
    private function totals(?array $filters): Collection
    {
        return IncomeReport::totalsBySource(
            $filters['period']['from'] ?? null,
            $filters['period']['until'] ?? null,
            array_map('intval', $filters['sources']['values'] ?? []),
        );
    }
}
