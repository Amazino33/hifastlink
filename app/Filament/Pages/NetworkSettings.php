<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanSyncService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NetworkSettings extends Page
{
    protected string $view = 'filament.pages.network-settings';

    protected static ?string $navigationLabel = 'Network';

    protected static ?string $title = 'Network Settings';

    protected static ?int $navigationSort = 21;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-signal';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    public bool $global_speed_enabled = false;

    public int $global_speed_upload = 1024;

    public int $global_speed_download = 2048;

    public string $basmelcare_api_url = '';

    public string $basmelcare_api_key = '';

    public string $gameshop_api_url = '';

    public string $gameshop_api_key = '';

    public bool $free_wifi_enabled = false;

    public string $free_wifi_plan_id = '';

    public string $free_wifi_instruction = '';

    // Grace Pass (Instant Free Internet before Login)
    public bool $grace_pass_enabled = false;

    public int $grace_pass_duration_minutes = 5;

    public int $grace_pass_data_limit_mb = 50;

    public int $grace_pass_speed_upload = 1024;

    public int $grace_pass_speed_download = 2048;

    public int $grace_pass_cooldown_hours = 24;

    public string $grace_pass_button_text = 'Get 5 Mins Free Internet';

    public string $grace_pass_instruction = 'Need internet to receive your WhatsApp code or test our connection? Tap below for free instant access.';

    public bool $show_sub_account_passwords = false;

    public bool $hotspot_detection_enabled = false;

    public function mount(): void
    {
        $this->global_speed_enabled = AppSetting::bool('global_speed_enabled', false);
        $this->global_speed_upload = (int) AppSetting::get('global_speed_upload', 1024);
        $this->global_speed_download = (int) AppSetting::get('global_speed_download', 2048);

        $this->basmelcare_api_url = AppSetting::get('basmelcare_api_url', '');
        $this->basmelcare_api_key = AppSetting::get('basmelcare_api_key', '');

        $this->gameshop_api_url = AppSetting::get('gameshop_api_url', '');
        $this->gameshop_api_key = AppSetting::get('gameshop_api_key', '');

        $this->free_wifi_enabled = AppSetting::bool('free_wifi_enabled', false);
        $this->free_wifi_plan_id = AppSetting::get('free_wifi_plan_id', '');
        $this->free_wifi_instruction = AppSetting::get('free_wifi_instruction', '');

        $this->grace_pass_enabled = AppSetting::bool('grace_pass_enabled', false);
        $this->grace_pass_duration_minutes = (int) AppSetting::get('grace_pass_duration_minutes', 5);
        $this->grace_pass_data_limit_mb = (int) AppSetting::get('grace_pass_data_limit_mb', 50);
        $this->grace_pass_speed_upload = (int) AppSetting::get('grace_pass_speed_upload', 1024);
        $this->grace_pass_speed_download = (int) AppSetting::get('grace_pass_speed_download', 2048);
        $this->grace_pass_cooldown_hours = (int) AppSetting::get('grace_pass_cooldown_hours', 24);
        $this->grace_pass_button_text = AppSetting::get('grace_pass_button_text', 'Get 5 Mins Free Internet');
        $this->grace_pass_instruction = AppSetting::get('grace_pass_instruction', 'Need internet to receive your WhatsApp code or test our connection? Tap below for free instant access.');

        $this->show_sub_account_passwords = AppSetting::bool('show_sub_account_passwords', false);
        $this->hotspot_detection_enabled = AppSetting::bool('hotspot_detection_enabled', false);
    }

    public function save(): void
    {
        $this->validate([
            'global_speed_upload' => ['required', 'integer', 'min:0', 'max:1000000'],
            'global_speed_download' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        AppSetting::set('global_speed_enabled', $this->global_speed_enabled ? '1' : '0');
        AppSetting::set('global_speed_upload', (string) $this->global_speed_upload);
        AppSetting::set('global_speed_download', (string) $this->global_speed_download);

        Notification::make()->title('Network settings saved.')->success()->send();
    }

    public function savePharmacy(): void
    {
        $this->validate([
            'basmelcare_api_url' => ['nullable', 'url', 'max:255'],
            'basmelcare_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        AppSetting::set('basmelcare_api_url', $this->basmelcare_api_url);
        AppSetting::set('basmelcare_api_key', $this->basmelcare_api_key);

        Notification::make()->title('Pharmacy integration saved.')->success()->send();
    }

    public function saveGameShop(): void
    {
        $this->validate([
            'gameshop_api_url' => ['nullable', 'url', 'max:255'],
            'gameshop_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        AppSetting::set('gameshop_api_url', $this->gameshop_api_url);
        AppSetting::set('gameshop_api_key', $this->gameshop_api_key);

        Notification::make()->title('Brothers Crib integration saved.')->success()->send();
    }

    public function saveSubAccounts(): void
    {
        AppSetting::set('show_sub_account_passwords', $this->show_sub_account_passwords ? '1' : '0');
        Notification::make()->title('Sub-account settings saved.')->success()->send();
    }

    public function saveHotspotDetection(): void
    {
        AppSetting::set('hotspot_detection_enabled', $this->hotspot_detection_enabled ? '1' : '0');
        Notification::make()->title('Hotspot detection setting saved.')->success()->send();
    }

    public function saveFreeWifi(): void
    {
        $this->validate([
            'free_wifi_plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'free_wifi_instruction' => ['nullable', 'string', 'max:500'],
        ]);

        AppSetting::set('free_wifi_enabled', $this->free_wifi_enabled ? '1' : '0');
        AppSetting::set('free_wifi_plan_id', (string) $this->free_wifi_plan_id);
        AppSetting::set('free_wifi_instruction', $this->free_wifi_instruction);

        Notification::make()->title('Free WiFi trial settings saved.')->success()->send();
    }

    public function saveGracePass(): void
    {
        $this->validate([
            'grace_pass_duration_minutes' => ['required', 'integer', 'min:1', 'max:180'],
            'grace_pass_data_limit_mb'    => ['required', 'integer', 'min:0', 'max:10000'],
            'grace_pass_speed_upload'     => ['required', 'integer', 'min:0', 'max:1000000'],
            'grace_pass_speed_download'   => ['required', 'integer', 'min:0', 'max:1000000'],
            'grace_pass_cooldown_hours'   => ['required', 'integer', 'min:0', 'max:720'],
            'grace_pass_button_text'      => ['required', 'string', 'max:100'],
            'grace_pass_instruction'      => ['nullable', 'string', 'max:500'],
        ]);

        AppSetting::set('grace_pass_enabled',          $this->grace_pass_enabled ? '1' : '0');
        AppSetting::set('grace_pass_duration_minutes', (string) $this->grace_pass_duration_minutes);
        AppSetting::set('grace_pass_data_limit_mb',    (string) $this->grace_pass_data_limit_mb);
        AppSetting::set('grace_pass_speed_upload',     (string) $this->grace_pass_speed_upload);
        AppSetting::set('grace_pass_speed_download',   (string) $this->grace_pass_speed_download);
        AppSetting::set('grace_pass_cooldown_hours',   (string) $this->grace_pass_cooldown_hours);
        AppSetting::set('grace_pass_button_text',      $this->grace_pass_button_text);
        AppSetting::set('grace_pass_instruction',      $this->grace_pass_instruction);

        Notification::make()->title('Grace Pass settings saved.')->success()->send();
    }

    public function getPlansProperty(): array
    {
        return Plan::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($p) => [
                $p->id => $p->name.' — '.$p->validity_days.' day(s)'
                    .($p->data_limit ? '' : ', Unlimited'),
            ])
            ->toArray();
    }

    public function getSelectedPlanProperty(): ?Plan
    {
        return $this->free_wifi_plan_id ? Plan::find($this->free_wifi_plan_id) : null;
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
