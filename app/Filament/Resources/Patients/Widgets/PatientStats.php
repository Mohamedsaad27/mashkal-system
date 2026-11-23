<?php

namespace App\Filament\Resources\Patients\Widgets;

use App\Filament\Resources\Patients\Pages\ListPatients;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPIs above the patients table; they follow the table's current filters and search.
 */
class PatientStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected function getTablePage(): string
    {
        return ListPatients::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery()->reorder();

        $total = (clone $query)->count();
        $residents = (clone $query)->whereNull('discharged_on')->count();
        $discharged = $total - $residents;
        $admittedThisMonth = (clone $query)
            ->whereBetween('admitted_on', [now()->startOfMonth()->toDateString(), now()->toDateString()])
            ->count();

        return [
            Stat::make('إجمالي المرضى', number_format($total))
                ->description('حسب الفلاتر الحالية')
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary'),
            Stat::make('مقيم حاليًا', number_format($residents))
                ->description($total > 0 ? round($residents / $total * 100).'% من الإجمالي' : null)
                ->icon(Heroicon::OutlinedHome)
                ->color('success'),
            Stat::make('خرجوا', number_format($discharged))
                ->icon(Heroicon::OutlinedArrowRightStartOnRectangle),
            Stat::make('دخلوا هذا الشهر', number_format($admittedThisMonth))
                ->description(now()->locale('ar')->translatedFormat('F Y'))
                ->icon(Heroicon::OutlinedCalendarDays),
        ];
    }
}
