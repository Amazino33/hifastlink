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
    public $totalUsers = 0;
    public $todayTransactions = 0;
    public $monthlyRevenue = 0;
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
            $activeSessionsQuery->where('nasipaddress', $router->ip_address);
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
                $usernames = RadAcct::where('nasipaddress', $router->ip_address)
                    ->distinct('username')->pluck('username');

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
            $dataConsumedQuery->where('nasipaddress', $router->ip_address);
        }
        $dataConsumedBytes = (int) $dataConsumedQuery->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        $this->dataConsumed = Number::fileSize($dataConsumedBytes);

        // Additional stats
        $this->totalUsers = User::count();
        $this->todayTransactions = Transaction::where('status', 'completed')->whereDate('created_at', today())->count();
        $this->monthlyRevenue = (float) Transaction::where('status', 'completed')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount');
    }

    public function render()
    {
        return view('livewire.admin-stats');
    }
}