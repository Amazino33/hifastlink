<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;

class StatsController extends Controller
{
    public function getStats(Request $request)
    {
        try {
            $routerParam = $request->input('router_id');
            $router = null;

            if ($routerParam && strtolower($routerParam) !== 'all') {
                if (is_numeric($routerParam)) {
                    $router = \App\Models\Router::find((int) $routerParam);
                }
                if (! $router) {
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

            // Active subscribers — always scope by RadAcct sessions for the router
            // (transaction-based scoping misattributes users who paid at one router
            //  but are currently connected to another)
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

            // Data consumed: all active sessions + sessions that ended today
            $dataConsumedBytes = (int) RadAcct::query()
                ->where(fn($q) => $q->whereNull('acctstoptime')->orWhereDate('acctstoptime', today()))
                ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
                ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));

            return response()->json([
                'online_users'       => $onlineUsers,
                'today_revenue'      => $todayRevenue,
                'active_subscribers' => $activeSubscribers,
                'data_consumed'      => Number::fileSize($dataConsumedBytes),
            ]);

        } catch (\Exception $e) {
            Log::error('Stats API error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
