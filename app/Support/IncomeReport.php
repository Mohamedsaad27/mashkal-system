<?php

namespace App\Support;

use App\Models\Income;
use App\Models\IncomeSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Income aggregates for the dashboard and reports.
 *
 * Every query goes through Eloquent so trashed income is always excluded.
 */
class IncomeReport
{
    /**
     * Total income per source within an optional period.
     *
     * @param  list<int>  $sourceIds  empty means all sources
     * @return Collection<int, array{source: string, count: int, total: float, percentage: float}> keyed by source id
     */
    public static function totalsBySource(?string $from, ?string $until, array $sourceIds = []): Collection
    {
        $rows = self::incomeBetween($from, $until)
            ->when($sourceIds !== [], fn (Builder $query): Builder => $query->whereIn('income_source_id', $sourceIds))
            ->selectRaw('income_source_id, COUNT(*) as incomes_count, SUM(amount) as total')
            ->groupBy('income_source_id')
            ->get();

        $sourceNames = IncomeSource::query()->pluck('name', 'id');
        $grandTotal = (float) $rows->sum('total');

        return $rows
            ->keyBy('income_source_id')
            ->map(fn (Income $row): array => [
                'source' => $sourceNames[$row->income_source_id] ?? '—',
                'count' => (int) $row->incomes_count,
                'total' => (float) $row->total,
                'percentage' => $grandTotal > 0 ? round((float) $row->total / $grandTotal * 100, 1) : 0.0,
            ])
            ->sortByDesc('total');
    }

    /**
     * Total income received in the given month.
     */
    public static function totalForMonth(CarbonImmutable $month): float
    {
        return (float) self::incomeBetween(
            $month->startOfMonth()->toDateString(),
            $month->endOfMonth()->toDateString(),
        )->sum('amount');
    }

    /**
     * @return Builder<Income>
     */
    private static function incomeBetween(?string $from, ?string $until): Builder
    {
        return Income::query()
            ->when($from, fn (Builder $query, string $date): Builder => $query->whereDate('received_on', '>=', $date))
            ->when($until, fn (Builder $query, string $date): Builder => $query->whereDate('received_on', '<=', $date));
    }
}
