<?php

namespace App\Filament\Pages;

use App\Models\SystemLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class SystemLogs extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.system-logs';
    protected static ?string $navigationLabel = 'System Logs';
    protected static ?string $title           = 'System Logs';
    protected static ?int    $navigationSort  = 99;

    public static function getNavigationIcon(): string   { return 'heroicon-o-bug-ant'; }
    public static function getNavigationGroup(): ?string { return 'Settings'; }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    public string $search      = '';
    public string $levelFilter = '';

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedLevelFilter(): void { $this->resetPage(); }

    #[Computed]
    public function logs()
    {
        return SystemLog::query()
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('message', 'like', "%{$this->search}%")
                          ->orWhere('type', 'like', "%{$this->search}%")
                          ->orWhere('url', 'like', "%{$this->search}%");
                });
            })
            ->when($this->levelFilter, fn ($q) => $q->where('level', $this->levelFilter))
            ->orderByDesc('occurred_at')
            ->paginate(30);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'total'   => SystemLog::count(),
            'errors'  => SystemLog::where('level', 'error')->count(),
            'today'   => SystemLog::whereDate('occurred_at', today())->count(),
            'last24h' => SystemLog::where('occurred_at', '>=', now()->subHours(24))->count(),
        ];
    }

    public function clearOld(): void
    {
        $deleted = SystemLog::where('occurred_at', '<', now()->subDays(30))->delete();
        Notification::make()->title("Cleared {$deleted} log(s) older than 30 days.")->success()->send();
        unset($this->logs, $this->stats);
    }

    public function clearAll(): void
    {
        SystemLog::truncate();
        Notification::make()->title('All system logs cleared.')->success()->send();
        unset($this->logs, $this->stats);
    }
}
