<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class PaymentSettings extends Page
{
    protected string $view = 'filament.pages.payment-settings';
    protected static ?string $navigationLabel = 'Payment Gateways';
    protected static ?string $title           = 'Payment Gateway Settings';
    protected static ?int    $navigationSort  = 18;

    public static function getNavigationIcon(): string   { return 'heroicon-o-credit-card'; }
    public static function getNavigationGroup(): ?string { return 'Settings'; }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->hasRole('super_admin'));
    }

    // Default primary gateway: 'flutterwave' or 'paystack'
    public string $payment_gateway = 'flutterwave';

    // Flutterwave credentials
    public string $flw_public_key  = '';
    public string $flw_secret_key  = '';
    public string $flw_secret_hash = '';
    public string $flw_base_url    = 'https://api.flutterwave.com/v3';

    // Paystack credentials
    public string $paystack_public_key  = '';
    public string $paystack_secret_key  = '';
    public string $paystack_payment_url = 'https://api.paystack.co';

    public function mount(): void
    {
        $this->payment_gateway = AppSetting::get('payment_gateway', config('services.payment_gateway', 'flutterwave'));

        $this->flw_public_key  = AppSetting::get('flw_public_key', config('services.flutterwave.public_key', ''));
        $this->flw_secret_key  = AppSetting::get('flw_secret_key', config('services.flutterwave.secret_key', ''));
        $this->flw_secret_hash = AppSetting::get('flw_secret_hash', config('services.flutterwave.secret_hash', ''));
        $this->flw_base_url    = AppSetting::get('flw_base_url', config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'));

        $this->paystack_public_key  = AppSetting::get('paystack_public_key', config('services.paystack.public_key', ''));
        $this->paystack_secret_key  = AppSetting::get('paystack_secret_key', config('services.paystack.secret_key', ''));
        $this->paystack_payment_url = AppSetting::get('paystack_payment_url', config('services.paystack.payment_url', 'https://api.paystack.co'));
    }

    public function saveSettings(): void
    {
        $this->validate([
            'payment_gateway'      => ['required', 'string', 'in:flutterwave,paystack'],
            'flw_public_key'       => ['nullable', 'string', 'max:255'],
            'flw_secret_key'       => ['nullable', 'string', 'max:255'],
            'flw_secret_hash'      => ['nullable', 'string', 'max:255'],
            'flw_base_url'         => ['required', 'string', 'url'],
            'paystack_public_key'  => ['nullable', 'string', 'max:255'],
            'paystack_secret_key'  => ['nullable', 'string', 'max:255'],
            'paystack_payment_url' => ['required', 'string', 'url'],
        ]);

        AppSetting::set('payment_gateway', trim($this->payment_gateway));

        AppSetting::set('flw_public_key',  trim($this->flw_public_key));
        AppSetting::set('flw_secret_key',  trim($this->flw_secret_key));
        AppSetting::set('flw_secret_hash', trim($this->flw_secret_hash));
        AppSetting::set('flw_base_url',    rtrim(trim($this->flw_base_url), '/'));

        AppSetting::set('paystack_public_key',  trim($this->paystack_public_key));
        AppSetting::set('paystack_secret_key',  trim($this->paystack_secret_key));
        AppSetting::set('paystack_payment_url', rtrim(trim($this->paystack_payment_url), '/'));

        Notification::make()
            ->title('Payment settings saved.')
            ->body('Gateway credentials and default gateway updated successfully.')
            ->success()
            ->send();
    }

    public function testFlutterwave(): void
    {
        $secretKey = trim($this->flw_secret_key) ?: AppSetting::get('flw_secret_key', config('services.flutterwave.secret_key'));
        $baseUrl = rtrim(trim($this->flw_base_url) ?: AppSetting::get('flw_base_url', config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3')), '/');

        if (empty($secretKey)) {
            Notification::make()
                ->title('Flutterwave Secret Key Missing')
                ->body('Please enter your Flutterwave Secret Key before testing connection.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::withToken($secretKey)
                ->timeout(12)
                ->get("{$baseUrl}/banks/NG");

            if ($response->successful() && ($response->json('status') ?? '') === 'success') {
                Notification::make()
                    ->title('Flutterwave Connected Successfully!')
                    ->body('Your secret key is valid and connected to Flutterwave API.')
                    ->success()
                    ->send();
            } else {
                $errorMsg = $response->json('message') ?? 'Invalid API response';
                Notification::make()
                    ->title('Flutterwave Connection Failed')
                    ->body("API Error: {$errorMsg} (HTTP {$response->status()})")
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Connection Error')
                ->body("Could not connect to Flutterwave: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }

    public function testPaystack(): void
    {
        $secretKey = trim($this->paystack_secret_key) ?: AppSetting::get('paystack_secret_key', config('services.paystack.secret_key'));
        $apiUrl = rtrim(trim($this->paystack_payment_url) ?: AppSetting::get('paystack_payment_url', config('services.paystack.payment_url', 'https://api.paystack.co')), '/');

        if (empty($secretKey)) {
            Notification::make()
                ->title('Paystack Secret Key Missing')
                ->body('Please enter your Paystack Secret Key before testing connection.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::withToken($secretKey)
                ->timeout(12)
                ->get("{$apiUrl}/bank");

            if ($response->successful() && ($response->json('status') ?? false) === true) {
                Notification::make()
                    ->title('Paystack Connected Successfully!')
                    ->body('Your secret key is valid and connected to Paystack API.')
                    ->success()
                    ->send();
            } else {
                $errorMsg = $response->json('message') ?? 'Invalid API response';
                Notification::make()
                    ->title('Paystack Connection Failed')
                    ->body("API Error: {$errorMsg} (HTTP {$response->status()})")
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Connection Error')
                ->body("Could not connect to Paystack: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }
}
