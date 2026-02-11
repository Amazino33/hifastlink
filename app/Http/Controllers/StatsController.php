<?php

namespace App\Http\Controllers;

use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class StatsController extends Controller
{
    public function getStats(Request $request)
    {
        $routerId = $request->input('router_id');
        $shouldFilterByRouter = is_string($routerId) && $routerId !== '' && strtolower($routerId) !== 'all';

        $activeSessionsQuery = RadAcct::query()->whereNull('acctstoptime');
        if ($shouldFilterByRouter) {
            $activeSessionsQuery->where(function ($query) use ($routerId) {
                $query->where('nasipaddress', $routerId)
                    ->orWhere('nasidentifier', $routerId);
            });
        }

        // Count of active sessions
        $onlineUsers = (clone $activeSessionsQuery)->count();

        // Total sales for today
        $todayRevenue = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        // Total users with valid plans
        $activeSubscribers = User::query()
            ->whereNotNull('plan_id')
            ->whereNotNull('plan_expiry')
            ->where('plan_expiry', '>', now())
            ->count();

        // Total data used today (sum of acctinputoctets + acctoutputoctets)
        $dataConsumedQuery = RadAcct::query()->whereDate('acctstarttime', today());
        if ($shouldFilterByRouter) {
            $dataConsumedQuery->where(function ($query) use ($routerId) {
                $query->where('nasipaddress', $routerId)
                    ->orWhere('nasidentifier', $routerId);
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
