<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminStats extends Component
{
    public $onlineUsers = 0;
    public $todayRevenue = 0;
    public $activeSubscribers = 0;
    public $dataConsumed = '0 B';
    public $currentRouter = 'all';

    public function mount()
    {
        $this->currentRouter = request()->input('router_id', 'all');
        $this->updateStats($this->currentRouter);
    }

    #[On('routerChanged')]
    public function updateStats($routerId)
    {
        $this->currentRouter = $routerId;
        $router = null;

        if ($routerId && strtolower($routerId) !== 'all') {
            if (is_numeric($routerId)) {
                $router = \App\Models\Router::find((int) $routerId);
            }
            if (! $router) {
                $router = \App\Models\Router::where('ip_address', $routerId)->orWhere('nas_identifier', $routerId)->first();
            }
        }

        $activeSessionsQuery = RadAcct::query()->whereNull('acctstoptime');
        if ($router) {
            $activeSessionsQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address)
                  ->orWhere('nas_identifier', $router->nas_identifier)
                  ->orWhere('nas_identifier', $router->identity ?? '');
            });
        }
        $this->onlineUsers = $activeSessionsQuery->distinct('username')->count('username');

        $todayRevenueQuery = Transaction::query()->where('status', 'completed')->whereDate('created_at', today());
        if ($router) $todayRevenueQuery->where('router_id', $router->id);
        $this->todayRevenue = (float) $todayRevenueQuery->sum('amount');

        $activeSubscribersQuery = User::query()->whereNotNull('plan_id')->whereNotNull('plan_expiry')->where('plan_expiry', '>', now());
        if ($router) {
            $userIds = Transaction::where('router_id', $router->id)->distinct('user_id')->pluck('user_id');
            if ($userIds->isNotEmpty()) {
                $activeSubscribersQuery->whereIn('id', $userIds);
            } else {
                $usernames = RadAcct::where(function($q) use ($router){
                    $q->where('nasipaddress', $router->ip_address)
                      ->orWhere('nas_identifier', $router->nas_identifier)
                      ->orWhere('nas_identifier', $router->identity ?? '');
                })->distinct('username')->pluck('username');

                if ($usernames->isNotEmpty()) {
                    $activeSubscribersQuery->whereIn('username', $usernames);
                } else {
                    $activeSubscribersQuery->whereRaw('1 = 0');
                }
            }
        }
        $this->activeSubscribers = $activeSubscribersQuery->count();

        $dataConsumedQuery = RadAcct::whereDate('acctstarttime', today());
        if ($router) {
            $dataConsumedQuery->where(function ($q) use ($router) {
                $q->where('nasipaddress', $router->ip_address)
                  ->orWhere('nas_identifier', $router->nas_identifier)
                  ->orWhere('nas_identifier', $router->identity ?? '');
            });
        }
        $dataConsumedBytes = (int) $dataConsumedQuery->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        $this->dataConsumed = Number::fileSize($dataConsumedBytes);
    }

    public function render()
    {
        return view('livewire.admin-stats');
    }
}