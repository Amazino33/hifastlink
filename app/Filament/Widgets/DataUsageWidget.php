<?php

namespace App\Filament\Widgets;

use App\Models\RadAcct;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class DataUsageWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $routerId = request()->input('router_id');

        // Build base query for optional router filtering
        $monthThisQuery = RadAcct::whereMonth('acctstarttime', now()->month)
            ->whereYear('acctstarttime', now()->year);
        $monthLastQuery = RadAcct::whereMonth('acctstarttime', now()->subMonth()->month)
            ->whereYear('acctstarttime', now()->subMonth()->year);
        $totalDataQuery = RadAcct::query();
        $activeSessionsQuery = RadAcct::whereNull('acctstoptime');

        if ($routerId && strtolower($routerId) !== 'all') {
            $monthThisQuery->where(function($q) use ($routerId) {
                $q->where('nasipaddress', $routerId)
                  ->orWhere('nasidentifier', $routerId);
            });
            $monthLastQuery->where(function($q) use ($routerId) {
                $q->where('nasipaddress', $routerId)
                  ->orWhere('nasidentifier', $routerId);
            });
            $totalDataQuery->where(function($q) use ($routerId) {
                $q->where('nasipaddress', $routerId)
                  ->orWhere('nasidentifier', $routerId);
            });
            $activeSessionsQuery->where(function($q) use ($routerId) {
                $q->where('nasipaddress', $routerId)
                  ->orWhere('nasidentifier', $routerId);
            });
        }

        // Total data usage this month
        $dataThisMonth = $monthThisQuery->sum(DB::raw('acctinputoctets + acctoutputoctets'));

        // Total data usage last month
        $dataLastMonth = $monthLastQuery->sum(DB::raw('acctinputoctets + acctoutputoctets'));

        // Total data usage all time
        $totalData = $totalDataQuery->sum(DB::raw('acctinputoctets + acctoutputoctets'));

        // Active sessions count
        $activeSessions = $activeSessionsQuery->count();

        // Calculate growth
        $growthPercentage = $dataLastMonth > 0 
            ? round((($dataThisMonth - $dataLastMonth) / $dataLastMonth) * 100, 1)
            : ($dataThisMonth > 0 ? 100 : 0);

        $growthDescription = $growthPercentage >= 0 
            ? "{$growthPercentage}% increase from last month"
            : abs($growthPercentage) . "% decrease from last month";

        return [
            Stat::make('Data This Month', Number::fileSize($dataThisMonth))
                ->description($growthDescription)
                ->descriptionIcon($growthPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthPercentage >= 0 ? 'info' : 'warning')
                ->chart([
                    $dataLastMonth / (1024 * 1024 * 1024), // Convert to GB for chart
                    $dataThisMonth / (1024 * 1024 * 1024),
                ]),

            Stat::make('Total Data Usage', Number::fileSize($totalData))
                ->description('All-time bandwidth')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),

            Stat::make('Active Sessions', $activeSessions)
                ->description('Current connections')
                ->descriptionIcon('heroicon-m-wifi')
                ->color('success'),
        ];
    }
}
