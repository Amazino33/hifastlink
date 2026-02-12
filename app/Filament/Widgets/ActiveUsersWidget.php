<?php

namespace App\Filament\Widgets;

use App\Models\RadAcct;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ActiveUsersWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total users count
        $totalUsers = User::count();

        $routerId = request()->input('router_id');

        // Currently online users (active sessions)
        $onlineUsersQuery = RadAcct::whereNull('acctstoptime');
        if ($routerId && strtolower($routerId) !== 'all') {
            $onlineUsersQuery->where(function($q) use ($routerId) {
                $q->where('nasipaddress', $routerId)
                  ->orWhere('nasidentifier', $routerId);
            });
        }

        $onlineUsers = $onlineUsersQuery
            ->distinct('username')
            ->count('username');

        // Users created this month
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Users created last month
        $newUsersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Calculate growth percentage
        $growthPercentage = $newUsersLastMonth > 0 
            ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100, 1)
            : ($newUsersThisMonth > 0 ? 100 : 0);

        $growthDescription = $growthPercentage >= 0 
            ? "{$growthPercentage}% increase from last month"
            : abs($growthPercentage) . "% decrease from last month";

        return [
            Stat::make('Total Users', $totalUsers)
                ->description($growthDescription)
                ->descriptionIcon($growthPercentage >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growthPercentage >= 0 ? 'success' : 'danger')
                ->chart([
                    $newUsersLastMonth,
                    $newUsersThisMonth,
                ]),

            Stat::make('Online Now', $onlineUsers)
                ->description('Active connections')
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),

            Stat::make('New This Month', $newUsersThisMonth)
                ->description('User registrations')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),
        ];
    }
}
