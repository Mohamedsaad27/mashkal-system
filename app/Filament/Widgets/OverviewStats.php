<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use App\Support\ExpenseReport;
use App\Support\IncomeReport;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $thisMonth = CarbonImmutable::now();
        $monthName = $thisMonth->locale('ar')->translatedFormat('F Y');
        $thisMonthTotal = ExpenseReport::totalForMonth($thisMonth);
        $lastMonthTotal = ExpenseReport::totalForMonth($thisMonth->subMonthNoOverflow());

        return [
            Stat::make('دخل الشهر الحالي (ج.م)', self::amount(IncomeReport::totalForMonth($thisMonth)))
                ->description($monthName)
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->color('success'),
            Stat::make('مصاريف الشهر الحالي (ج.م)', self::amount($thisMonthTotal))
                ->description($monthName)
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('primary'),
            Stat::make('مصاريف الشهر الماضي (ج.م)', self::amount($lastMonthTotal))
                ->description(self::comparison($thisMonthTotal, $lastMonthTotal))
                ->icon(Heroicon::OutlinedCalendar),
            Stat::make('المرضى المقيمين حاليًا', Patient::query()->resident()->count())
                ->description('إجمالي المرضى المسجلين: '.Patient::query()->count())
                ->icon(Heroicon::OutlinedUsers)
                ->color('success'),
        ];
    }

    private static function comparison(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return 'لا توجد مصاريف في الشهر الماضي';
        }

        $change = round(($current - $previous) / $previous * 100, 1);

        return $change >= 0
            ? "الشهر الحالي أعلى بنسبة {$change}%"
            : 'الشهر الحالي أقل بنسبة '.abs($change).'%';
    }

    private static function amount(float $amount): string
    {
        return number_format($amount, 2);
    }
}
