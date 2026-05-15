<?php

namespace App\Filament\Concerns;

use App\Models\Router;
use Illuminate\Support\Facades\Schema;

trait HasRouterFilter
{
    protected function getSelectedRouter(): ?Router
    {
        $routerId = $this->filters['router_id'] ?? 'all';
        if (! $routerId || strtolower((string) $routerId) === 'all') {
            return null;
        }
        return Router::find((int) $routerId);
    }

    protected function applyRouterFilter($query, Router $router): void
    {
        $ips = array_values(array_filter([$router->ip_address, $router->vpn_ip]));

        $query->where(function ($q) use ($router, $ips) {
            $q->whereIn('nasipaddress', $ips);
            if (Schema::hasColumn('radacct', 'nasidentifier')) {
                $q->orWhere('nasidentifier', $router->nas_identifier);
                if (! empty($router->identity)) {
                    $q->orWhere('nasidentifier', $router->identity);
                }
            }
        });
    }
}
