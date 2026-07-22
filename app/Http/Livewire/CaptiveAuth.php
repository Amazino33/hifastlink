<?php

namespace App\Http\Livewire;

use App\Models\Device;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\User;
use App\Models\Voucher;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class CaptiveAuth extends Component
{
    public string $identifier = '';
    public string $error      = '';
    public bool   $noplan     = false;

    public ?string $linkLogin = null;
    public ?string $mac       = null;
    public ?string $ip        = null;
    public ?string $router    = null;

    public function mount(): void
    {
        $this->linkLogin = request()->get('link-login')
            ?? request()->get('link-login-only')
            ?? request()->get('link_login')
            ?? request()->get('link-orig');

        $this->mac    = request()->get('mac');
        $this->ip     = request()->get('ip');
        $this->router = request()->get('router');

        // Known device — try silent auto-login before showing the form
        if ($this->mac && $this->linkLogin) {
            $this->tryMacAutoLogin();
        }
    }

    // ── MAC auto-login (runs on every hotspot connection for known devices) ──

    private function tryMacAutoLogin(): void
    {
        $device = Device::where('mac', strtoupper($this->mac))->with('user')->first();

        if (! $device) return;

        // Regular subscriber — check plan and bridge
        if ($device->user_id && $device->user) {
            if ((new SubscriptionService())->canConnectToHotspot($device->user)) {
                Log::info('CaptiveAuth: MAC auto-login (user)', ['mac' => $this->mac, 'user_id' => $device->user->id]);
                $this->completeLogin($device->user);
            }
            // Plan expired → fall through, show form
            return;
        }

        // Voucher device — re-use the stored RADIUS credentials if not expired
        $meta        = is_array($device->meta) ? $device->meta : [];
        $voucherCode = $meta['voucher_code'] ?? null;

        if (! $voucherCode) return;

        $radUsername = 'vch_' . strtolower($voucherCode);
        $rad         = RadCheck::where('username', $radUsername)->where('attribute', 'Cleartext-Password')->first();

        if (! $rad) return;

        $expiryRow = RadCheck::where('username', $radUsername)->where('attribute', 'Expiration')->first();
        if ($expiryRow && \Carbon\Carbon::parse($expiryRow->value)->isPast()) {
            return; // Voucher expired → show form
        }

        Log::info('CaptiveAuth: MAC auto-login (voucher)', ['mac' => $this->mac, 'voucher' => $voucherCode]);
        $device->update(['last_seen' => now(), 'is_connected' => true]);
        session(['bridge_completed' => true]);
        $this->bridgeToRouter($radUsername, $rad->value, $this->linkLogin, route('captive.connected'));
    }

    // ── Single-field connect ─────────────────────────────────────────────────

    public function connect(): void
    {
        $input        = trim($this->identifier);
        $this->error  = '';
        $this->noplan = false;

        if (empty($input)) {
            $this->error = 'Please enter your phone number, email, username, or voucher code.';
            return;
        }

        // Voucher code (e.g. VCH-XXXXX)
        if (Voucher::isVoucherCode($input)) {
            $this->activateVoucher(strtoupper($input));
            return;
        }

        $user = $this->findUser($input);

        if (! $user) {
            $this->error = 'No account found. Please subscribe at hifastlink.com first.';
            return;
        }

        if (! (new SubscriptionService())->canConnectToHotspot($user)) {
            $this->noplan = true;
            return;
        }

        $this->completeLogin($user);
    }

    // ── User lookup: email → phone → username ────────────────────────────────

    private function findUser(string $input): ?User
    {
        if (str_contains($input, '@')) {
            return User::where('email', $input)->first();
        }

        $digits = preg_replace('/\D/', '', $input);
        if (strlen($digits) >= 7) {
            $user = User::where('phone', 'like', '%' . substr($digits, -10))->first();
            if ($user) return $user;
        }

        return User::where('username', $input)->first();
    }

    // ── Voucher activation (no user account required) ────────────────────────

    private function activateVoucher(string $code): void
    {
        $voucher = Voucher::findValid($code);

        if (! $voucher) {
            $this->error = 'This voucher is invalid, expired, or has no remaining uses.';
            return;
        }

        $creator = $voucher->creator;

        if ($creator && ! (new SubscriptionService())->canConnectToHotspot($creator)) {
            $this->error = "This voucher's plan has expired or run out of data.";
            return;
        }

        // Use a stable RADIUS identity derived from the voucher code
        $radUsername = 'vch_' . strtolower($code);

        $existing    = RadCheck::where('username', $radUsername)->where('attribute', 'Cleartext-Password')->first();
        $radPassword = $existing?->value ?? Str::random(12);

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radPassword]
        );

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => (string) $voucher->max_uses]
        );

        $expiresAt = $creator?->plan_expiry ?? $voucher->expires_at;
        if ($expiresAt) {
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => \Carbon\Carbon::parse($expiresAt)->format('d M Y H:i')]
            );
        }

        $plan = $voucher->plan ?? $creator?->plan;
        if ($plan) {
            $uploadKbps   = $voucher->speed_limit_upload   ?? $plan->speed_limit_upload;
            $downloadKbps = $voucher->speed_limit_download ?? $plan->speed_limit_download;
            if ($uploadKbps || $downloadKbps) {
                RadReply::updateOrCreate(
                    ['username' => $radUsername, 'attribute' => 'Mikrotik-Rate-Limit'],
                    ['op' => ':=', 'value' => ($uploadKbps ?? 0) . 'k/' . ($downloadKbps ?? 0) . 'k']
                );
            }

            if (! $voucher->is_unlimited && $plan->data_limit) {
                $limitMb = $plan->limit_unit === 'GB'
                    ? (int) ($plan->data_limit * 1024)
                    : (int) $plan->data_limit;
                RadReply::updateOrCreate(
                    ['username' => $radUsername, 'attribute' => 'Mikrotik-Total-Limit'],
                    ['op' => ':=', 'value' => (string) ($limitMb * 1048576)]
                );
            }
        }

        $voucher->consume();

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
                    'meta'         => ['voucher_code' => $code],
                ]
            );
        }

        if (! $this->linkLogin) {
            $this->error = 'Voucher activated but no hotspot session found. Please reconnect to the WiFi.';
            return;
        }

        session(['bridge_completed' => true]);
        $this->bridgeToRouter($radUsername, $radPassword, $this->linkLogin, route('captive.connected'));
    }

    // ── Post-lookup bridge ───────────────────────────────────────────────────

    private function completeLogin(User $user): void
    {
        if ($this->mac) {
            try {
                Device::upsertFromLogin(
                    $user,
                    $this->mac,
                    $this->router,
                    $this->ip ?? request()->ip(),
                    request()->userAgent()
                );
            } catch (\Throwable $e) {
                Log::warning('CaptiveAuth: device upsert failed — ' . $e->getMessage());
            }
        }

        if (! $this->linkLogin) {
            $this->redirect(route('dashboard'));
            return;
        }

        $rad         = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
        $radPassword = $rad?->value ?? $user->radius_password;

        if (! $radPassword) {
            $this->error = 'Account not set up for hotspot access. Please contact support.';
            return;
        }

        session(['bridge_completed' => true]);
        $this->bridgeToRouter($user->username, $radPassword, $this->linkLogin, route('captive.connected'));
    }

    private function bridgeToRouter(string $username, string $password, string $linkLogin, string $linkOrig): void
    {
        session([
            'bridge_username'   => $username,
            'bridge_password'   => $password,
            'bridge_link_login' => $linkLogin,
            'bridge_link_orig'  => $linkOrig,
            'bridge_mac'        => $this->mac,
            'bridge_ip'         => $this->ip,
            'bridge_router'     => $this->router,
        ]);

        $this->redirect(route('captive.bridge'));
    }

    public function render()
    {
        return view('livewire.captive-auth');
    }
}
