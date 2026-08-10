<?php

namespace App\Http\Livewire;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\RadAcct;
use App\Models\RadCheck;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class GameShopVoucher extends Component
{
    public string $step = 'code'; // code | success

    public string $code  = '';
    public string $error = '';

    public ?string $expiresAt     = null;
    public ?string $validityHours = null;

    // Captive portal context (passed from MikroTik redirect)
    public ?string $linkLogin = null;
    public ?string $mac       = null;
    public ?string $ip        = null;
    public ?string $router    = null;

    public function mount(): void
    {
        $this->linkLogin = request()->query('link-login')
            ?? request()->query('link-login-only')
            ?? request()->query('link_login');

        $this->mac    = request()->query('mac');
        $this->ip     = request()->query('ip');
        $this->router = request()->query('router');
    }

    public function validateCode(): void
    {
        $this->validate(['code' => 'required|string|max:20']);

        $code = strtoupper(preg_replace('/\s+/', '', trim($this->code)));

        // If RADIUS credentials for this code already exist and haven't expired,
        // skip the API call and reconnect directly (same device, same window).
        $existing = RadCheck::where('username', $code)
            ->where('attribute', 'Cleartext-Password')
            ->first();

        if ($existing) {
            $expiry = RadCheck::where('username', $code)
                ->where('attribute', 'Expiration')
                ->first();

            $isExpired = $expiry && Carbon::createFromFormat('d M Y H:i', $expiry->value)->isPast();

            if (! $isExpired) {
                if ($this->mac) {
                    $boundDevice = Device::where('meta->gameshop_code', $code)->first();
                    if ($boundDevice && strtoupper($this->mac) !== strtoupper($boundDevice->mac)) {
                        $this->error = 'This code has already been used on a different device.';
                        return;
                    }
                }

                $this->code = $code;
                $this->activate();
                return;
            }
        }

        $apiUrl = AppSetting::get('gameshop_api_url', '');
        $apiKey = AppSetting::get('gameshop_api_key', '');

        if (! $apiUrl || ! $apiKey) {
            $this->error = 'Game shop integration is not configured. Please contact support.';
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->post($apiUrl, ['code' => $code]);

            if (! $response->successful() || ! $response->json('valid')) {
                $this->error = $response->json('message', 'Invalid or expired code. Check the receipt and try again.');
                return;
            }

            $this->code          = strtoupper($response->json('code', $code));
            $this->expiresAt     = $response->json('expires_at');
            $this->validityHours = $response->json('validity_hours', 2);
            $this->error         = '';

            $this->activate();
        } catch (\Throwable $e) {
            Log::error('[GameShopVoucher] API call failed: ' . $e->getMessage());
            $this->error = 'Could not reach Brothers Crib. Please try again.';
        }
    }

    private function activate(): void
    {
        $radUsername = $this->code;

        $existing    = RadCheck::where('username', $radUsername)
            ->where('attribute', 'Cleartext-Password')
            ->first();
        $radPassword = $existing?->value ?? Str::random(12);

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radPassword]
        );

        // Clear stale sessions on other routers so Simultaneous-Use is not blocked.
        $staleQuery = RadAcct::forUser($radUsername)->whereNull('acctstoptime');
        if ($this->router) {
            $staleQuery->where('nas_identifier', '!=', $this->router);
        }
        $staleQuery->update([
            'acctstoptime'       => now(),
            'acctterminatecause' => 'Admin-Reset',
        ]);

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => '1']
        );

        if ($this->expiresAt) {
            $expiry = Carbon::parse($this->expiresAt);
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $expiry->format('d M Y H:i')]
            );
        }

        if ($this->mac) {
            Device::updateOrCreate(
                ['mac' => strtoupper($this->mac)],
                [
                    'user_id'      => null,
                    'ip'           => $this->ip ?? request()->ip(),
                    'user_agent'   => request()->userAgent(),
                    'first_seen'   => now(),
                    'last_seen'    => now(),
                    'is_connected' => true,
                    'meta'         => ['gameshop_code' => $radUsername],
                ]
            );
        }

        if ($this->linkLogin) {
            session([
                'bridge_username'   => $radUsername,
                'bridge_password'   => $radPassword,
                'bridge_link_login' => $this->linkLogin,
                'bridge_link_orig'  => route('captive.connected'),
                'bridge_mac'        => $this->mac,
                'bridge_ip'         => $this->ip,
                'bridge_router'     => $this->router,
                'bridge_completed'  => true,
            ]);

            $this->redirect(route('captive.bridge'));
            return;
        }

        $this->step  = 'success';
        $this->error = '';
    }

    public function goBack(): void
    {
        $this->step  = 'code';
        $this->code  = '';
        $this->error = '';
    }

    public function render()
    {
        return view('livewire.gameshop-voucher');
    }
}
