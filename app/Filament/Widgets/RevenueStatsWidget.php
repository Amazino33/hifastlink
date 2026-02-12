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
        $routerId = request()->input('router_id');

        // Helper: get user ids that had sessions on the router in given date range
        $userIdsForPeriod = function ($start, $end) use ($routerId) {
            $query = \App\Models\RadAcct::query();
            $query->whereBetween('acctstarttime', [$start, $end]);
            if ($routerId && strtolower($routerId) !== 'all') {
                $query->where(function($q) use ($routerId) {
                    $q->where('nasipaddress', $routerId)
                      ->orWhere('nasidentifier', $routerId);
                });
            }

            $usernames = $query->distinct('username')->pluck('username')->filter()->unique();
            if ($usernames->isEmpty()) return collect([]);

            return \App\Models\User::whereIn('username', $usernames)->pluck('id');
        };

        // Total revenue from completed transactions this month
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $userIds = $userIdsForPeriod($periodStart, $periodEnd);

        $revenueThisMonthQuery = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
        if ($userIds->isNotEmpty()) {
            $revenueThisMonthQuery->whereIn('user_id', $userIds);
        } elseif ($routerId && strtolower($routerId) !== 'all') {
            // No users matched - zero revenue for this router
            $revenueThisMonthQuery->whereRaw('1 = 0');
        }
        $revenueThisMonth = $revenueThisMonthQuery->sum('amount');

        // Total revenue from completed transactions last month
        $periodStartLast = now()->subMonth()->startOfMonth();
        $periodEndLast = now()->subMonth()->endOfMonth();
        $userIdsLast = $userIdsForPeriod($periodStartLast, $periodEndLast);

        $revenueLastMonthQuery = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year);
        if ($userIdsLast->isNotEmpty()) {
            $revenueLastMonthQuery->whereIn('user_id', $userIdsLast);
        } elseif ($routerId && strtolower($routerId) !== 'all') {
            $revenueLastMonthQuery->whereRaw('1 = 0');
        }
        $revenueLastMonth = $revenueLastMonthQuery->sum('amount');

        // All-time revenue
        $totalRevenueQuery = Transaction::where('status', 'completed');
        if ($routerId && strtolower($routerId) !== 'all') {
            $allUsernamesQuery = \App\Models\RadAcct::query();
            $allUsernamesQuery->where(function($q) use ($routerId) {
                $q->where('nasipaddress', $routerId)
                  ->orWhere('nasidentifier', $routerId);
            });
            $usernamesAll = $allUsernamesQuery->distinct('username')->pluck('username')->filter()->unique();
            $userIdsAll = \App\Models\User::whereIn('username', $usernamesAll)->pluck('id');
            if ($userIdsAll->isNotEmpty()) {
                $totalRevenueQuery->whereIn('user_id', $userIdsAll);
            } else {
                $totalRevenueQuery->whereRaw('1 = 0');
            }
        }
        $totalRevenue = $totalRevenueQuery->sum('amount');

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
