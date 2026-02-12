<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Router;

class RouterFilterWidget extends Widget
{
    protected static string $view = 'filament.widgets.router-filter-widget';

    public function getViewData(): array
    {
        $allRouters = Router::where('is_active', true)
            ->orderBy('name')
            ->get();

        $current = request()->input('router_id', 'all');

        return [
            'allRouters' => $allRouters,
            'currentRouter' => $current,
        ];
    }
}
