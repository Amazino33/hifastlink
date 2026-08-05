<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\Plan;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FreeWifiSettings extends Page
{
    protected string $view = 'filament.pages.free-wifi-settings';
    protected static ?string $navigationLabel = 'Free WiFi Trial';
    protected static ?string $title           = 'Free WiFi Trial Settings';
    protected static ?int    $navigationSort  = 22;

    public static function getNavigationIcon(): string   { return 'heroicon-o-wifi'; }
    public static function getNavigationGroup(): ?string { return 'Settings'; }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    public bool   $free_wifi_enabled = false;
    public string $free_wifi_plan_id = '';

    public function mount(): void
    {
        $this->free_wifi_enabled = AppSetting::bool('free_wifi_enabled', false);
        $this->free_wifi_plan_id = AppSetting::get('free_wifi_plan_id', '');
    }

    public function save(): void
    {
        $this->validate([
            'free_wifi_plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        AppSetting::set('free_wifi_enabled', $this->free_wifi_enabled ? '1' : '0');
        AppSetting::set('free_wifi_plan_id', (string) $this->free_wifi_plan_id);

        Notification::make()->title('Free WiFi settings saved.')->success()->send();
    }

    public function getPlansProperty(): array
    {
        return Plan::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($p) => [
                $p->id => $p->name . ' — ' . $p->validity_days . ' day(s)'
                    . ($p->data_limit ? '' : ', Unlimited'),
            ])
            ->toArray();
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        return $this->free_wifi_plan_id
            ? Plan::find($this->free_wifi_plan_id)
            : null;
    }
}
