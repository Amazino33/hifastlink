<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Router;

class RouterFilter extends Component
{
    public $selectedRouter = 'all';

    public function mount()
    {
        $this->selectedRouter = request()->input('router_id', 'all');
    }

    public function selectRouter($routerId)
    {
        $this->selectedRouter = $routerId;
        $this->dispatch('routerChanged', routerId: $routerId)->to(\App\Livewire\AdminStats::class);
    }

    public function render()
    {
        $allRouters = Router::where('is_active', true)->orderBy('name')->get();

        return view('livewire.router-filter', [
            'allRouters' => $allRouters,
        ]);
    }
}