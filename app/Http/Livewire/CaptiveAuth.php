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

    // Brand — passed from the captive-portal view when router has custom branding
    public ?string $brandName    = null;
    public ?string $brandColor   = null;
    public ?string $brandLogoUrl = null;

    public function mount(): void
    {
        $this->linkLogin = request()->query('link-login')
            ?? request()->query('link-login-only')
            ?? request()->query('link_login')
            ?? request()->query('link-orig');

        $this->mac    = request()->query('mac');
        $this->ip     = request()->query('ip');
        $this->router = request()->query('router');

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

        // ── Binding moment ──────────────────────────────────────────────
        // The device (MAC) and the paid plan are both in hand right here.
        // Register the MAC in RADIUS so the router can recognise this device
        // on its own next time — no portal, no browser bridge.
        $this->bindDeviceToPlan($user);

        if (! $this->linkLogin) {
            $this->redirect(route('dashboard'));
            return;
        }

        $rad         = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
        $radPassword = $rad?->value ?? $user->radius_password;

        if (! $radPassword) {
            Log::warning('CaptiveAuth: no RADIUS password for ' . $user->username . ' — RadCheck may be corrupted or missing');
            $this->error = 'Account not set up for hotspot access. Please contact support.';
            return;
        }

        session(['bridge_completed' => true]);
        $this->bridgeToRouter($user->username, $radPassword, $this->linkLogin, route('captive.connected'));
    }

    // ── Bind a device's MAC to the user's plan in RADIUS ─────────────────────
    // This is what makes reconnection seamless: once written, the MikroTik
    // router can authenticate this MAC directly against RADIUS and let the
    // device online without ever showing the portal again — until the plan
    // expires, at which point these rows stop authorising it.
    //
    // It mirrors exactly what activateVoucher() already does, but keyed on the
    // device MAC instead of a voucher code.
    private function bindDeviceToPlan(User $user): void
    {
        // No MAC (e.g. a desktop browser, not a hotspot device) → nothing to bind.
        if (! $this->mac) {
            return;
        }

        // The MAC, normalised the same way we normalise it everywhere else.
        // This becomes the RADIUS "username" for the device.
        $radUsername = strtoupper($this->mac);
        $plan        = $user->plan;

        // 1. Password row. MikroTik MAC-authentication presents the device's
        //    MAC as the password, so we store the MAC as the Cleartext-Password.
        //    (⚠ Verify this against your router — see the note I'll give you.)
        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radUsername]
        );

        // 2. Expiry row. When the plan ends, RADIUS stops accepting this MAC
        //    and the device naturally falls back to the portal.
        if ($user->plan_expiry) {
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => \Carbon\Carbon::parse($user->plan_expiry)->format('d M Y H:i')]
            );
        }

        // 3. Speed-limit row, copied from the plan — same as the voucher flow.
        if ($plan && ($plan->speed_limit_upload || $plan->speed_limit_download)) {
            RadReply::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => ($plan->speed_limit_upload ?? 0) . 'k/' . ($plan->speed_limit_download ?? 0) . 'k']
            );
        }

        Log::info('CaptiveAuth: bound device to plan', [
            'mac'     => $radUsername,
            'user_id' => $user->id,
        ]);
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
