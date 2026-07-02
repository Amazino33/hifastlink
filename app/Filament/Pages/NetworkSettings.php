<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\User;
use App\Services\PlanSyncService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NetworkSettings extends Page
{
    protected string $view = 'filament.pages.network-settings';
    protected static ?string $navigationLabel = 'Network';
    protected static ?string $title           = 'Network Settings';
    protected static ?int    $navigationSort  = 21;

    public static function getNavigationIcon(): string   { return 'heroicon-o-signal'; }
    public static function getNavigationGroup(): ?string { return 'Settings'; }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    public bool $global_speed_enabled  = false;
    public int  $global_speed_upload   = 1024;
    public int  $global_speed_download = 2048;

    public function mount(): void
    {
        $this->global_speed_enabled  = AppSetting::bool('global_speed_enabled', false);
        $this->global_speed_upload   = (int) AppSetting::get('global_speed_upload', 1024);
        $this->global_speed_download = (int) AppSetting::get('global_speed_download', 2048);
    }

    public function save(): void
    {
        $this->validate([
            'global_speed_upload'   => ['required', 'integer', 'min:0', 'max:1000000'],
            'global_speed_download' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        AppSetting::set('global_speed_enabled',  $this->global_speed_enabled ? '1' : '0');
        AppSetting::set('global_speed_upload',   (string) $this->global_speed_upload);
        AppSetting::set('global_speed_download', (string) $this->global_speed_download);

        Notification::make()->title('Network settings saved.')->success()->send();
    }

    public function applyGlobally(): void
    {
        $users = User::whereNotNull('username')
            ->where(function ($q) {
                $q->whereNotNull('plan_id')
                  ->orWhere(fn ($q2) => $q2->whereNull('plan_id')
                      ->whereNotNull('plan_expiry')
                      ->where('plan_expiry', '>', now()));
            })
            ->get();

        $count = 0;
        foreach ($users as $user) {
            if ($user->isAdmin()) {
                continue;
            }
            PlanSyncService::syncUserPlan($user);
            $count++;
        }

        Notification::make()
            ->title("Rate limit applied to {$count} active user(s).")
            ->success()
            ->send();
    }
}
