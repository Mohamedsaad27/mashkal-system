<?php

namespace App\Filament\Resources\Expenses\Widgets;

use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Models\ExpenseCategory;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPIs above the expenses table; they follow the table's current filters and search.
 */
class ExpenseStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected function getTablePage(): string
    {
        return ListExpenses::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery()->reorder();

        $total = (float) (clone $query)->sum('amount');
        $count = (clone $query)->count();

        $topCategory = (clone $query)
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->first();

        return [
            Stat::make('إجمالي المصاريف (ج.م)', number_format($total, 2))
                ->description('حسب الفلاتر الحالية')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('primary'),
            Stat::make('عدد المصاريف', number_format($count))
                ->icon(Heroicon::OutlinedListBullet),
            Stat::make('متوسط المصروف (ج.م)', number_format($count > 0 ? $total / $count : 0, 2))
                ->icon(Heroicon::OutlinedCalculator),
            Stat::make('أعلى سبب صرف', $topCategory ? ExpenseCategory::find($topCategory->expense_category_id)?->name ?? '—' : '—')
                ->description($topCategory ? Money::format((float) $topCategory->total) : 'لا توجد مصاريف')
                ->icon(Heroicon::OutlinedTag),
        ];
    }
}
