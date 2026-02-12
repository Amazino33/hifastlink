<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.admin-stats-widget';

    protected string|int|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $routerParam = request()->input('router_id', 'all');
        $router = null;

        if ($routerParam && strtolower($routerParam) !== 'all') {
            if (is_numeric($routerParam)) {
                $router = \App\Models\Router::find((int) $routerParam);
            }
            if (! $router) {
                // Use nas_identifier as primary identifier, fallback to ip_address
                $router = \App\Models\Router::where('nas_identifier', $routerParam)
                    ->orWhere('ip_address', $routerParam)
                    ->first();
            }
        }

        // online users
        $activeSessionsQuery = RadAcct::query()->whereNull('acctstoptime');
        if ($router) {
            $activeSessionsQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address);

                if (\Illuminate\Support\Facades\Schema::hasColumn('radacct', 'nasidentifier')) {
                    $q->orWhere('nasidentifier', $router->nas_identifier)
                      ->orWhere('nasidentifier', $router->identity ?? '');
                }
            });
        }
        $onlineUsers = $activeSessionsQuery->distinct('username')->count('username');

        // revenue
        $todayRevenueQuery = Transaction::query()->where('status', 'completed')->whereDate('created_at', today());
        if ($router) $todayRevenueQuery->where('router_id', $router->id);
        $todayRevenue = (float) $todayRevenueQuery->sum('amount');

        // active subscribers
        $activeSubscribersQuery = User::query()->whereNotNull('plan_id')->whereNotNull('plan_expiry')->where('plan_expiry', '>', now());
        if ($router) {
            $userIds = Transaction::where('router_id', $router->id)->distinct('user_id')->pluck('user_id');
            if ($userIds->isNotEmpty()) {
                $activeSubscribersQuery->whereIn('id', $userIds);
            } else {
                $usernames = RadAcct::where(function($q) use ($router){
                    $q->where('nasipaddress', $router->ip_address);

                    if (\Illuminate\Support\Facades\Schema::hasColumn('radacct', 'nasidentifier')) {
                        $q->orWhere('nasidentifier', $router->nas_identifier)
                          ->orWhere('nasidentifier', $router->identity ?? '');
                    }
                })->distinct('username')->pluck('username');

                if ($usernames->isNotEmpty()) {
                    $activeSubscribersQuery->whereIn('username', $usernames);
                } else {
                    $activeSubscribersQuery->whereRaw('1 = 0');
                }
            }
        }
        $activeSubscribers = $activeSubscribersQuery->count();

        // data consumed
        $dataConsumedQuery = RadAcct::whereDate('acctstarttime', today());
        if ($router) {
            $dataConsumedQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address);

                if (\Illuminate\Support\Facades\Schema::hasColumn('radacct', 'nasidentifier')) {
                    $q->orWhere('nasidentifier', $router->nas_identifier)
                      ->orWhere('nasidentifier', $router->identity ?? '');
                }
            });
        }
        $dataConsumedBytes = (int) $dataConsumedQuery->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        // additional stats
        $totalUsers = User::count();
        $todayTransactions = Transaction::where('status', 'completed')->whereDate('created_at', today())->count();
        $monthlyRevenue = (float) Transaction::where('status', 'completed')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');

        $recentSessions = RadAcct::query()->whereDate('acctstarttime', today())
            ->when($router, fn($q) => $q->where('nasipaddress', $router->ip_address))
            ->orderBy('acctstarttime', 'desc')
            ->limit(10)
            ->get();

        return [
            'onlineUsers' => $onlineUsers,
            'todayRevenue' => $todayRevenue,
            'activeSubscribers' => $activeSubscribers,
            'dataConsumed' => Number::fileSize($dataConsumedBytes),
            'totalUsers' => $totalUsers,
            'todayTransactions' => $todayTransactions,
            'monthlyRevenue' => $monthlyRevenue,
            'recentSessions' => $recentSessions,
            'currentRouter' => $routerParam,
        ];
    }
}
