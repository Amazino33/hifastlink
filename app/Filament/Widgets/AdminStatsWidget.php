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

        // Online users
        $onlineUsers = RadAcct::query()
            ->whereNull('acctstoptime')
            ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
            ->distinct('username')
            ->count('username');

        // Today's revenue
        $todayRevenue = (float) Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        // Active subscribers — scoped by RadAcct sessions so a user is counted
        // under the router they are actually connected to, not where they paid
        $activeSubscribersQuery = User::query()
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_expiry')
            ->where('plan_expiry', '>', now());

        if ($router) {
            $usernames = RadAcct::query()
                ->where(fn($q) => $this->applyRouterFilter($q, $router))
                ->distinct('username')
                ->pluck('username');

            $activeSubscribersQuery->when(
                $usernames->isNotEmpty(),
                fn($q) => $q->whereIn('username', $usernames),
                fn($q) => $q->whereRaw('1 = 0')
            );
        }
        $activeSubscribers = $activeSubscribersQuery->count();

        // Data consumed today
        $dataConsumedBytes = (int) RadAcct::query()
            ->whereDate('acctstarttime', today())
            ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
            ->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        // Total users seen on this router (via RadAcct history)
        $totalUsersQuery = User::query();
        if ($router) {
            $usernames = RadAcct::query()
                ->where(fn($q) => $this->applyRouterFilter($q, $router))
                ->distinct('username')
                ->pluck('username');

            $totalUsersQuery->when(
                $usernames->isNotEmpty(),
                fn($q) => $q->whereIn('username', $usernames),
                fn($q) => $q->whereRaw('1 = 0')
            );
        }
        $totalUsers = $totalUsersQuery->count();

        $todayTransactions = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->count();

        $monthlyRevenue = (float) Transaction::query()
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->when($router, fn($q) => $q->where('router_id', $router->id))
            ->sum('amount');

        $recentSessions = RadAcct::query()
            ->whereDate('acctstarttime', today())
            ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
            ->orderBy('acctstarttime', 'desc')
            ->limit(10)
            ->get();

        return [
            'onlineUsers'       => $onlineUsers,
            'todayRevenue'      => $todayRevenue,
            'activeSubscribers' => $activeSubscribers,
            'dataConsumed'      => Number::fileSize($dataConsumedBytes),
            'totalUsers'        => $totalUsers,
            'todayTransactions' => $todayTransactions,
            'monthlyRevenue'    => $monthlyRevenue,
            'recentSessions'    => $recentSessions,
            'currentRouter'     => $routerParam,
        ];
    }

    private function applyRouterFilter($query, \App\Models\Router $router)
    {
        $query->where(function ($q) use ($router) {
            $q->where('nasipaddress', $router->ip_address);
            if (Schema::hasColumn('radacct', 'nasidentifier')) {
                $q->orWhere('nasidentifier', $router->nas_identifier);
                if (! empty($router->identity)) {
                    $q->orWhere('nasidentifier', $router->identity);
                }
            }
        });
    }
}
