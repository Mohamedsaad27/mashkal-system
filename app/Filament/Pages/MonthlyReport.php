<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MonthlyExpensesChart;
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
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Month-by-month comparison of total expenses over the last 12 months.
 */
class MonthlyReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'المقارنة الشهرية';

    protected static ?string $title = 'المقارنة الشهرية للمصاريف';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->months())
            ->columns([
                TextColumn::make('label')
                    ->label('الشهر')
                    ->weight('bold'),
                TextColumn::make('total')
                    ->label('إجمالي المصاريف')
                    ->formatStateUsing(fn (float|string|null $state): string => Money::format($state)),
                TextColumn::make('change')
                    ->label('التغير عن الشهر السابق')
                    ->placeholder('—')
                    ->suffix('%')
                    ->color(fn (?float $state): ?string => match (true) {
                        $state === null => null,
                        $state > 0 => 'danger',
                        $state < 0 => 'success',
                        default => null,
                    }),
            ])
            ->paginated(false);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonthlyExpensesChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('تصدير Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => XlsxDownload::make(
                    'المقارنة-الشهرية-'.now()->format('Y-m-d'),
                    ['الشهر', 'إجمالي المصاريف (ج.م)', 'التغير عن الشهر السابق %'],
                    array_map(
                        fn (array $month): array => [$month['label'], $month['total'], $month['change']],
                        array_values($this->months()),
                    ),
                )),
        ];
    }

    /**
     * Newest month first, each with its change from the month before.
     *
     * @return array<string, array{month: string, label: string, total: float, change: float|null}>
     */
    private function months(): array
    {
        $months = ExpenseReport::monthlyTotals();
        $rows = [];

        foreach ($months as $index => $month) {
            $previous = $months[$index - 1]['total'] ?? null;

            $rows[$month['month']] = [
                ...$month,
                'change' => $previous > 0 ? round(($month['total'] - $previous) / $previous * 100, 1) : null,
            ];
        }

        return array_reverse($rows, preserve_keys: true);
    }
}
