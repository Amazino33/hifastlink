<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasRouterFilter;
use App\Models\RadAcct;
use App\Models\Router;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class RouterBreakdownWidget extends Widget
{
    protected string $view = 'filament.widgets.router-breakdown-widget';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $tz    = 'Africa/Lagos';
        $today = now($tz)->toDateString();
        $month = now($tz)->month;
        $year  = now($tz)->year;

        $routers = Router::where('is_active', true)->orderBy('name')->get();

        // ── Batch: online users per NAS IP ────────────────────────────────────
        $onlineByIp = RadAcct::whereNull('acctstoptime')
            ->select('nasipaddress', DB::raw('COUNT(DISTINCT username) as cnt'))
            ->groupBy('nasipaddress')
            ->pluck('cnt', 'nasipaddress');

        // ── Batch: today's revenue per router_id ──────────────────────────────
        $todayRevenueById = Transaction::whereIn('status', ['completed', 'success'])
            ->whereDate('created_at', $today)
            ->whereNotNull('router_id')
            ->select('router_id', DB::raw('SUM(amount) as total'))
            ->groupBy('router_id')
            ->pluck('total', 'router_id');

        // ── Batch: monthly revenue per router_id ──────────────────────────────
        $monthlyRevenueById = Transaction::whereIn('status', ['completed', 'success'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->whereNotNull('router_id')
            ->select('router_id', DB::raw('SUM(amount) as total'))
            ->groupBy('router_id')
            ->pluck('total', 'router_id');

        // ── Batch: distinct usernames seen per NAS IP (for total users & subs) ─
        $usernamesByIp = RadAcct::select('nasipaddress', 'username')
            ->distinct()
            ->get()
            ->groupBy('nasipaddress')
            ->map(fn($rows) => $rows->pluck('username')->map(fn($u) => strtolower($u)));

        // ── Active-plan usernames (global set, check membership per router) ───
        $activePlanUsernames = User::whereNotNull('plan_id')
            ->whereNotNull('plan_expiry')
            ->where('plan_expiry', '>', now())
            ->pluck('username')
            ->map(fn($u) => strtolower($u))
            ->flip(); // flip for O(1) lookup

        // ── Batch: today's transactions count per router ───────────────────────
        $todayTxnById = Transaction::whereIn('status', ['completed', 'success'])
            ->whereDate('created_at', $today)
            ->whereNotNull('router_id')
            ->select('router_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('router_id')
            ->pluck('cnt', 'router_id');

        // ── Build per-router rows ─────────────────────────────────────────────
        $rows = $routers->map(function (Router $router) use (
            $onlineByIp, $todayRevenueById, $monthlyRevenueById,
            $usernamesByIp, $activePlanUsernames, $todayTxnById
        ) {
            $ip     = $router->ip_address;
            $vpnIp  = $router->vpn_ip;
            $nasId  = $router->nas_identifier;

            $onlineNow = (int) ($onlineByIp->get($ip, 0)) + (int) ($vpnIp ? $onlineByIp->get($vpnIp, 0) : 0);

            // Unique usernames seen on this router (by LAN IP, VPN IP, or NAS identifier)
            $usernames = $usernamesByIp->get($ip, collect())
                ->merge($vpnIp ? $usernamesByIp->get($vpnIp, collect()) : collect())
                ->merge($usernamesByIp->get($nasId, collect()))
                ->unique();

            $totalUsers   = $usernames->count();
            $activeSubs   = $usernames->filter(fn($u) => isset($activePlanUsernames[$u]))->count();

            return [
                'id'              => $router->id,
                'name'            => $router->name ?? $router->nas_identifier,
                'location'        => $router->location ?? '—',
                'is_online'       => $router->is_online,
                'last_seen_at'    => $router->last_seen_at,
                'online_now'      => $onlineNow,
                'active_subs'     => $activeSubs,
                'total_users'     => $totalUsers,
                'today_revenue'   => (float) ($todayRevenueById->get($router->id, 0)),
                'today_txns'      => (int) ($todayTxnById->get($router->id, 0)),
                'monthly_revenue' => (float) ($monthlyRevenueById->get($router->id, 0)),
            ];
        });

        return ['rows' => $rows];
    }
}
