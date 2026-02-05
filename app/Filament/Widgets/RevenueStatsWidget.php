<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\PendingSubscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RevenueStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Total revenue from completed transactions this month
        $revenueThisMonth = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Total revenue from completed transactions last month
        $revenueLastMonth = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        // All-time revenue
        $totalRevenue = Transaction::where('status', 'completed')
            ->sum('amount');

        // Pending subscriptions (awaiting payment)
        $pendingSubscriptions = PendingSubscription::count();

        // Calculate growth
        $growthPercentage = $revenueLastMonth > 0 
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        $growthDescription = $growthPercentage >= 0 
            ? "{$growthPercentage}% increase from last month"
            : abs($growthPercentage) . "% decrease from last month";

        return [
            Stat::make('Revenue This Month', '₦' . number_format($revenueThisMonth, 2))
                ->description($growthDescription)
                ->descriptionIcon($growthPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthPercentage >= 0 ? 'success' : 'danger')
                ->chart([
                    $revenueLastMonth,
                    $revenueThisMonth,
                ]),

            Stat::make('Total Revenue', '₦' . number_format($totalRevenue, 2))
                ->description('All-time earnings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending Subscriptions', $pendingSubscriptions)
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
