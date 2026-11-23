<?php

namespace App\Filament\Resources\Incomes\Widgets;

use App\Filament\Resources\Incomes\Pages\ListIncomes;
use App\Models\IncomeSource;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPIs above the income table; they follow the table's current filters and search.
 */
class IncomeStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected function getTablePage(): string
    {
        return ListIncomes::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery()->reorder();

        $total = (float) (clone $query)->sum('amount');
        $count = (clone $query)->count();

        $topSource = (clone $query)
            ->selectRaw('income_source_id, SUM(amount) as total')
            ->groupBy('income_source_id')
            ->orderByDesc('total')
            ->first();

        return [
            Stat::make('إجمالي الدخل (ج.م)', number_format($total, 2))
                ->description('حسب الفلاتر الحالية')
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->color('success'),
            Stat::make('عدد مرات الدخل', number_format($count))
                ->icon(Heroicon::OutlinedListBullet),
            Stat::make('متوسط الدخل (ج.م)', number_format($count > 0 ? $total / $count : 0, 2))
                ->icon(Heroicon::OutlinedCalculator),
            Stat::make('أعلى مصدر دخل', $topSource ? IncomeSource::find($topSource->income_source_id)?->name ?? '—' : '—')
                ->description($topSource ? Money::format((float) $topSource->total) : 'لا يوجد دخل')
                ->icon(Heroicon::OutlinedTag),
        ];
    }
}
