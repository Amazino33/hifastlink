<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasRouterFilter;
use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;

class OverviewStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters, HasRouterFilter;

    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $router        = $this->getSelectedRouter();
        $tz            = 'Africa/Lagos';
        $today         = now($tz)->toDateString();
        $radacctExists = Schema::hasTable('radacct');

        // ── Online users ──────────────────────────────────────────────────────
        $onlineUsers = $radacctExists
            ? RadAcct::whereNull('acctstoptime')
                ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
                ->distinct('username')
                ->count('username')
            : 0;

        // ── Active subscribers ────────────────────────────────────────────────
        $activeSubsQuery = User::whereNotNull('plan_id')
            ->whereNotNull('plan_expiry')
            ->where('plan_expiry', '>', now());

        if ($router && $radacctExists) {
            $usernames = RadAcct::where(fn($q) => $this->applyRouterFilter($q, $router))
                ->distinct('username')->pluck('username');
            $activeSubsQuery->when(
                $usernames->isNotEmpty(),
                fn($q) => $q->whereIn('username', $usernames),
                fn($q) => $q->whereRaw('1 = 0')
            );
        }
        $activeSubscribers = $activeSubsQuery->count();

        // ── Revenue ───────────────────────────────────────────────────────────
        $todayRevenue = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
            ->whereDate('created_at', $today)
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        $yesterdayRevenue = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
            ->whereDate('created_at', now($tz)->subDay()->toDateString())
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        $monthlyRevenue = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
            ->whereMonth('created_at', now($tz)->month)
            ->whereYear('created_at', now($tz)->year)
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        $lastMonthRevenue = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
            ->whereMonth('created_at', now($tz)->subMonth()->month)
            ->whereYear('created_at', now($tz)->subMonth()->year)
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        $totalRevenue = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        $todayTransactions = Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
            ->whereDate('created_at', $today)
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->count();

        // ── Users ─────────────────────────────────────────────────────────────
        $totalUsersQuery = User::query();
        if ($router) {
            $usernames = RadAcct::where(fn($q) => $this->applyRouterFilter($q, $router))
                ->distinct('username')->pluck('username');
            $totalUsersQuery->when(
                $usernames->isNotEmpty(),
                fn($q) => $q->whereIn('username', $usernames),
                fn($q) => $q->whereRaw('1 = 0')
            );
        }
        $totalUsers = $totalUsersQuery->count();

        $newUsersToday = User::whereDate('created_at', $today)->count();

        $newUsersThisMonth = User::whereMonth('created_at', now($tz)->month)
            ->whereYear('created_at', now($tz)->year)->count();

        $newUsersLastMonth = User::whereMonth('created_at', now($tz)->subMonth()->month)
            ->whereYear('created_at', now($tz)->subMonth()->year)->count();

        // ── Data in use (live sessions) ───────────────────────────────────────
        $dataInUseBytes = (int) RadAcct::whereNull('acctstoptime')
            ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
            ->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        // ── 7-day revenue sparkline ───────────────────────────────────────────
        $revenueSparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $revenueSparkline[] = (float) Transaction::whereIn('status', ['completed', 'success'])->where('gateway', 'paystack')
                ->whereDate('created_at', now($tz)->subDays($i)->toDateString())
                ->when($router, fn($q) => $q->where('router_id', $router->id))
                ->sum('amount');
        }

        // ── Trend descriptions ────────────────────────────────────────────────
        $todayRevenueTrend = match (true) {
            $yesterdayRevenue <= 0  => 'No revenue yesterday',
            $todayRevenue >= $yesterdayRevenue => '+' . round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100) . '% vs yesterday',
            default                 => '-' . round((($yesterdayRevenue - $todayRevenue) / $yesterdayRevenue) * 100) . '% vs yesterday',
        };

        $monthlyRevenueTrend = match (true) {
            $lastMonthRevenue <= 0  => $monthlyRevenue > 0 ? 'No data last month' : 'No revenue yet',
            $monthlyRevenue >= $lastMonthRevenue => '+' . round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100) . '% vs last month',
            default                 => '-' . round((($lastMonthRevenue - $monthlyRevenue) / $lastMonthRevenue) * 100) . '% vs last month',
        };

        $userGrowthTrend = match (true) {
            $newUsersLastMonth <= 0 => $newUsersThisMonth > 0 ? 'Growth from 0 last month' : 'No new users',
            $newUsersThisMonth >= $newUsersLastMonth => '+' . round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100) . '% vs last month',
            default                 => '-' . round((($newUsersLastMonth - $newUsersThisMonth) / $newUsersLastMonth) * 100) . '% vs last month',
        };

        return [
            Stat::make('Online Now', number_format($onlineUsers))
                ->description('Active connections')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),

            Stat::make('Active Subscribers', number_format($activeSubscribers))
                ->description('Plans not expired')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make("Today's Revenue", '₦' . number_format($todayRevenue, 0))
                ->description($todayRevenueTrend)
                ->descriptionIcon($todayRevenue >= $yesterdayRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayRevenue >= $yesterdayRevenue ? 'success' : 'danger')
                ->chart($revenueSparkline),

            Stat::make("Today's Transactions", number_format($todayTransactions))
                ->description('Paid plans today')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('warning'),

            Stat::make('Monthly Revenue', '₦' . number_format($monthlyRevenue, 0))
                ->description($monthlyRevenueTrend)
                ->descriptionIcon($monthlyRevenue >= $lastMonthRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyRevenue >= $lastMonthRevenue ? 'success' : 'danger'),

            Stat::make('All-time Revenue', '₦' . number_format($totalRevenue, 0))
                ->description('Total earnings to date')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Users', number_format($totalUsers))
                ->description($newUsersThisMonth . ' new this month')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('New Users Today', number_format($newUsersToday))
                ->description($userGrowthTrend)
                ->descriptionIcon($newUsersThisMonth >= $newUsersLastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($newUsersThisMonth >= $newUsersLastMonth ? 'success' : 'warning'),

            Stat::make('Data In Use', Number::fileSize($dataInUseBytes))
                ->description('Across all live sessions')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('primary'),
        ];
    }
}
