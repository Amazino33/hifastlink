<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Router;

class RouterFilterWidget extends Widget
{
    protected string $view = 'filament.widgets.router-filter-widget';

    public ?string $search = '';

    public function getViewData(): array
    {
        $query = Router::where('is_active', true);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%')
                  ->orWhere('nas_identifier', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            });
        }

        $allRouters = $query->orderBy('name')->get();

        $current = request()->input('router_id', 'all');
        $currentRouter = null;

        if ($current && strtolower($current) !== 'all') {
            $currentRouter = Router::where('nas_identifier', $current)
                ->orWhere('ip_address', $current)
                ->first();
        }

        return [
            'allRouters' => $allRouters,
            'currentRouter' => $current,
            'currentRouterModel' => $currentRouter,
        ];
    }

    // called via wire:click from the widget view
    public function selectRouter(string $routerId)
    {
        // redirect to same Filament dashboard route with router_id query param
        return redirect()->route('filament.admin.pages.custom-dashboard', ['router_id' => $routerId]);
    }
}
