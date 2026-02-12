<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Schema;

class StatsController extends Controller
{
    public function getStats(Request $request)
    {
        $routerParam = $request->input('router_id');
        $router = null;

        if ($routerParam && strtolower($routerParam) !== 'all') {
            // If numeric, assume router ID (FK)
            if (is_numeric($routerParam)) {
                $router = \App\Models\Router::find((int) $routerParam);
            }

            // Otherwise try to resolve by ip/identifier/identity
            if (! $router) {
                $lookupCol = Schema::hasColumn('routers', 'identity') ? 'identity' : 'nas_identifier';
                $router = \App\Models\Router::where('ip_address', $routerParam)
                    ->orWhere($lookupCol, $routerParam)
                    ->first();
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

        // Count of active sessions
        $onlineUsers = (clone $activeSessionsQuery)->count();

        // Total sales for today
        $todayRevenueQuery = Transaction::query()->where('status', 'completed')->whereDate('created_at', today());
        if ($router) {
            $todayRevenueQuery->where('router_id', $router->id);
        }
        $todayRevenue = $todayRevenueQuery->sum('amount');

        // Total users with valid plans
        $activeSubscribersQuery = User::query()
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_expiry')
            ->where('plan_expiry', '>', now());

        if ($router) {
            // If router provided, count users who have transactions linked to that router or active sessions on that router
            $userIds = Transaction::where('router_id', $router->id)->distinct('user_id')->pluck('user_id');
            if ($userIds->isNotEmpty()) {
                $activeSubscribersQuery->whereIn('id', $userIds);
            } else {
                // As fallback, also check RadAcct usernames that have sessions on the router
                $usernames = RadAcct::where(function($q) use ($router) {
                    $q->where('nasipaddress', $router->ip_address)
                      ->orWhere('nasidentifier', $router->nas_identifier)
                      ->orWhere('nasidentifier', $router->identity ?? '');
                })->distinct('username')->pluck('username');

                if ($usernames->isNotEmpty()) {
                    $activeSubscribersQuery->whereIn('username', $usernames);
                } else {
                    // No matching users -> return zero
                    $activeSubscribersQuery->whereRaw('1 = 0');
                }
            }
        }

        $activeSubscribers = $activeSubscribersQuery->count();

        // Total data used today (sum of acctinputoctets + acctoutputoctets)
        $dataConsumedQuery = RadAcct::query()->whereDate('acctstarttime', today());
        if ($router) {
            $dataConsumedQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address)
                  ->orWhere('nasidentifier', $router->nas_identifier)
                  ->orWhere('nasidentifier', $router->identity ?? '');
            });
        }

        $dataConsumedBytes = (int) $dataConsumedQuery->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));

        $stats = [
            'online_users' => $onlineUsers,
            'today_revenue' => (float) $todayRevenue,
            'active_subscribers' => $activeSubscribers,
            'data_consumed' => Number::fileSize($dataConsumedBytes),
        ];

        return response()->json($stats);
    }
}
