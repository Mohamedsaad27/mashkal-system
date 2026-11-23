<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Expense aggregates for the dashboard and reports.
 *
 * Every query goes through Eloquent so trashed expenses are always excluded.
 */
class ExpenseReport
{
    /**
     * Total expenses per category within an optional period.
     *
     * @param  list<int>  $categoryIds  empty means all categories
     * @return Collection<int, array{category: string, count: int, total: float, percentage: float}> keyed by category id
     */
    public static function totalsByCategory(?string $from, ?string $until, array $categoryIds = []): Collection
    {
        $rows = self::expensesBetween($from, $until)
            ->when($categoryIds !== [], fn (Builder $query): Builder => $query->whereIn('expense_category_id', $categoryIds))
            ->selectRaw('expense_category_id, COUNT(*) as expenses_count, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->get();

        $categoryNames = ExpenseCategory::query()->pluck('name', 'id');
        $grandTotal = (float) $rows->sum('total');

        return $rows
            ->keyBy('expense_category_id')
            ->map(fn (Expense $row): array => [
                'category' => $categoryNames[$row->expense_category_id] ?? '—',
                'count' => (int) $row->expenses_count,
                'total' => (float) $row->total,
                'percentage' => $grandTotal > 0 ? round((float) $row->total / $grandTotal * 100, 1) : 0.0,
            ])
            ->sortByDesc('total');
    }

    /**
     * Totals for each of the last N months, oldest first, including months with no expenses.
     *
     * @return list<array{month: string, label: string, total: float}>
     */
    public static function monthlyTotals(int $months = 12): array
    {
        $firstMonth = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);

        $totals = Expense::query()
            ->whereDate('spent_on', '>=', $firstMonth)
            ->get(['spent_on', 'amount'])
            ->groupBy(fn (Expense $expense): string => $expense->spent_on->format('Y-m'))
            ->map(fn (Collection $group): float => (float) $group->sum('amount'));

        $result = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $firstMonth->addMonths($i);
            $key = $month->format('Y-m');

            $result[] = [
                'month' => $key,
                'label' => $month->locale('ar')->translatedFormat('F Y'),
                'total' => $totals[$key] ?? 0.0,
            ];
        }

        return $result;
    }

    /**
     * Total spent in the given month.
     */
    public static function totalForMonth(CarbonImmutable $month): float
    {
        return (float) self::expensesBetween(
            $month->startOfMonth()->toDateString(),
            $month->endOfMonth()->toDateString(),
        )->sum('amount');
    }

    /**
     * Patients query annotated with their expense count, total cost and total payments.
     *
     * Trashed patients are included; trashed expenses and income are not.
     *
     * @return Builder<Patient>
     */
    public static function patientCostsQuery(): Builder
    {
        return Patient::query()
            ->withTrashed()
            ->withCount('expenses')
            ->withSum('expenses', 'amount')
            ->withSum('incomes', 'amount');
    }

    /**
     * @return Builder<Expense>
     */
    private static function expensesBetween(?string $from, ?string $until): Builder
    {
        return Expense::query()
            ->when($from, fn (Builder $query, string $date): Builder => $query->whereDate('spent_on', '>=', $date))
            ->when($until, fn (Builder $query, string $date): Builder => $query->whereDate('spent_on', '<=', $date));
    }
}
