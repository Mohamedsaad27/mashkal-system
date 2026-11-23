<?php

namespace App\Filament\Widgets;

use App\Support\ExpenseReport;
use Filament\Widgets\ChartWidget;

class MonthlyExpensesChart extends ChartWidget
{
    protected ?string $heading = 'المصاريف الشهرية (آخر 12 شهر)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = ExpenseReport::monthlyTotals();

        return [
            'datasets' => [
                [
                    'label' => 'إجمالي المصاريف (ج.م)',
                    'data' => array_column($months, 'total'),
                ],
            ],
            'labels' => array_column($months, 'label'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
