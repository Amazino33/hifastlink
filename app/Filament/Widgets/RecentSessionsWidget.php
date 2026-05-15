<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasRouterFilter;
use App\Models\RadAcct;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class RecentSessionsWidget extends Widget
{
    use InteractsWithPageFilters, HasRouterFilter;

    protected string $view = 'filament.widgets.recent-sessions-widget';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $router = $this->getSelectedRouter();

        $sessions = RadAcct::query()
            ->leftJoin('users', DB::raw('radacct.username COLLATE utf8mb4_unicode_ci'), '=', 'users.username')
            ->leftJoin('routers', 'routers.ip_address', '=', 'radacct.nasipaddress')
            ->select([
                'radacct.username',
                'radacct.nasipaddress',
                'radacct.framedipaddress',
                'radacct.callingstationid',
                'radacct.acctstarttime',
                'radacct.acctstoptime',
                'radacct.acctsessiontime',
                'radacct.acctinputoctets',
                'radacct.acctoutputoctets',
                'users.name as user_name',
                DB::raw('MIN(routers.name) as router_name'),
            ])
            ->when($router, fn($q) => $this->applyRouterFilter($q, $router))
            ->groupBy([
                'radacct.username',
                'radacct.nasipaddress',
                'radacct.framedipaddress',
                'radacct.callingstationid',
                'radacct.acctstarttime',
                'radacct.acctstoptime',
                'radacct.acctsessiontime',
                'radacct.acctinputoctets',
                'radacct.acctoutputoctets',
                'users.name',
            ])
            ->orderByDesc('radacct.acctstarttime')
            ->limit(25)
            ->get()
            ->map(function ($s) {
                $bytes        = (int) $s->acctinputoctets + (int) $s->acctoutputoctets;
                $isActive     = is_null($s->acctstoptime);
                $duration     = $s->acctsessiontime ? gmdate('H:i:s', $s->acctsessiontime) : '—';
                $startedAt    = $s->acctstarttime ? \Carbon\Carbon::parse($s->acctstarttime)->timezone('Africa/Lagos') : null;

                return [
                    'username'       => $s->username,
                    'user_name'      => $s->user_name ?? '—',
                    'router_name'    => $s->router_name ?? ($s->nasipaddress ?? '—'),
                    'ip'             => $s->framedipaddress ?? '—',
                    'mac'            => strtoupper($s->callingstationid ?? '—'),
                    'started_at'     => $startedAt?->format('M d, H:i'),
                    'started_human'  => $startedAt?->diffForHumans(),
                    'duration'       => $duration,
                    'data_used'      => Number::fileSize($bytes),
                    'is_active'      => $isActive,
                ];
            });

        return ['sessions' => $sessions];
    }
}
