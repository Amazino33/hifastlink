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
        $routerParam = request()->input('router_id');
        $router = null;

        if ($routerParam && strtolower($routerParam) !== 'all') {
            if (is_numeric($routerParam)) {
                $router = \App\Models\Router::find((int) $routerParam);
            }
            if (! $router) {
                $lookupCol = Schema::hasColumn('routers', 'identity') ? 'identity' : 'nas_identifier';
                $router = \App\Models\Router::where('ip_address', $routerParam)->orWhere($lookupCol, $routerParam)->first();
            }
        }

        $activeSessionsQuery = RadAcct::query()->whereNull('acctstoptime');
        if ($router) {
            $activeSessionsQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address)
                  ->orWhere('nasidentifier', $router->nas_identifier)
                  ->orWhere('nasidentifier', $router->identity ?? '');
            });
        }
        $onlineUsers = $activeSessionsQuery->distinct('username')->count('username');

        $todayRevenueQuery = Transaction::query()->where('status', 'completed')->whereDate('created_at', today());
        if ($router) $todayRevenueQuery->where('router_id', $router->id);
        $todayRevenue = (float) $todayRevenueQuery->sum('amount');

        $activeSubscribersQuery = User::query()->whereNotNull('plan_id')->whereNotNull('plan_expiry')->where('plan_expiry', '>', now());
        if ($router) {
            $userIds = Transaction::where('router_id', $router->id)->distinct('user_id')->pluck('user_id');
            if ($userIds->isNotEmpty()) {
                $activeSubscribersQuery->whereIn('id', $userIds);
            } else {
                $usernames = RadAcct::where(function($q) use ($router){
                    $q->where('nasipaddress', $router->ip_address)
                      ->orWhere('nasidentifier', $router->nas_identifier)
                      ->orWhere('nasidentifier', $router->identity ?? '');
                })->distinct('username')->pluck('username');

                if ($usernames->isNotEmpty()) {
                    $activeSubscribersQuery->whereIn('username', $usernames);
                } else {
                    $activeSubscribersQuery->whereRaw('1 = 0');
                }
            }
        }
        $activeSubscribers = $activeSubscribersQuery->count();

        $dataConsumedQuery = RadAcct::whereDate('acctstarttime', today());
        if ($router) {
            $dataConsumedQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address)
                  ->orWhere('nasidentifier', $router->nas_identifier)
                  ->orWhere('nasidentifier', $router->identity ?? '');
            });
        }
        $dataConsumedBytes = (int) $dataConsumedQuery->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        return [
            'onlineUsers' => $onlineUsers,
            'todayRevenue' => $todayRevenue,
            'activeSubscribers' => $activeSubscribers,
            'dataConsumed' => Number::fileSize($dataConsumedBytes),
        ];
    }
}
