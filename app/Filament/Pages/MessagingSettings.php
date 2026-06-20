<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MessagingSettings extends Page
{
    protected string $view = 'filament.pages.messaging-settings';
    protected static ?string $navigationLabel = 'Messaging';
    protected static ?string $title           = 'Messaging Settings';
    protected static ?int    $navigationSort  = 20;

    public static function getNavigationIcon(): string   { return 'heroicon-o-chat-bubble-left-right'; }
    public static function getNavigationGroup(): ?string { return 'Settings'; }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    // WhatsApp
    public string $instance_id   = '';
    public string $access_token  = '';
    public bool   $enabled       = false;
    public string $test_number   = '';

    // SMS fallback
    public bool   $sms_enabled    = false;
    public string $sms_provider   = 'termii';
    public string $sms_api_key    = '';
    public string $sms_api_secret = '';
    public string $sms_sender_id  = 'HiFastLink';

    // OTP
    public int $otp_window_minutes = 10;
    public int $otp_max_attempts   = 3;

    public function mount(): void
    {
        $this->instance_id  = AppSetting::get('wawp_instance_id', '');
        $this->access_token = AppSetting::get('wawp_access_token', '');
        $this->enabled      = AppSetting::bool('wawp_enabled', false);

        $this->sms_enabled    = AppSetting::bool('sms_enabled', false);
        $this->sms_provider   = AppSetting::get('sms_provider', 'termii');
        $this->sms_api_key    = AppSetting::get('sms_api_key', '');
        $this->sms_api_secret = AppSetting::get('sms_api_secret', '');
        $this->sms_sender_id  = AppSetting::get('sms_sender_id', 'HiFastLink');

        $this->otp_window_minutes = (int) AppSetting::get('otp_window_minutes', 10);
        $this->otp_max_attempts   = (int) AppSetting::get('otp_max_attempts', 3);
    }

    public function saveMessaging(): void
    {
        $this->validate([
            'instance_id'        => ['nullable', 'string', 'max:100'],
            'access_token'       => ['nullable', 'string', 'max:200'],
            'sms_api_key'        => ['nullable', 'string', 'max:200'],
            'sms_api_secret'     => ['nullable', 'string', 'max:200'],
            'sms_sender_id'      => ['nullable', 'string', 'max:20'],
            'otp_window_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'otp_max_attempts'   => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        AppSetting::set('wawp_instance_id',  trim($this->instance_id));
        AppSetting::set('wawp_access_token', trim($this->access_token));
        AppSetting::set('wawp_enabled',      $this->enabled ? '1' : '0');

        AppSetting::set('sms_enabled',    $this->sms_enabled ? '1' : '0');
        AppSetting::set('sms_provider',   $this->sms_provider);
        AppSetting::set('sms_api_key',    $this->sms_api_key);
        AppSetting::set('sms_api_secret', $this->sms_api_secret);
        AppSetting::set('sms_sender_id',  $this->sms_sender_id);

        AppSetting::set('otp_window_minutes', (string) $this->otp_window_minutes);
        AppSetting::set('otp_max_attempts',   (string) $this->otp_max_attempts);

        Notification::make()->title('Messaging settings saved.')->success()->send();
    }

    public function sendTest(): void
    {
        $this->validate(['test_number' => ['required', 'string', 'min:7']]);

        $sent = app(WhatsAppService::class)->send(
            $this->test_number,
            'This is a test message from HiFastLink. Your messaging integration is working correctly.'
        );

        if ($sent) {
            Notification::make()->title('Test message sent.')->success()->send();
        } else {
            Notification::make()
                ->title('Not sent — check log for details.')
                ->body('Enable WhatsApp or SMS and save credentials first.')
                ->warning()->send();
        }
    }
}
