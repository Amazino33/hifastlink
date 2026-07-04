<?php

namespace App\Http\Livewire;

use App\Models\Device;
use App\Models\Otp;
use App\Models\RadCheck;
use App\Models\User;
use App\Models\Voucher;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class CaptiveAuth extends Component
{
    public string $step = 'phone'; // phone | email | voucher_phone | otp
    public string $phone = '';
    public string $otp = '';
    public string $voucherCode = '';
    public string $email = '';
    public string $password = '';
    public string $error = '';
    public string $success = '';
    public bool $isVoucher = false;
    public int $resendCountdown = 0;

    // Tracks what the OTP verification should do after success
    public string $otpPurpose = 'login'; // login | voucher

    // Captive portal params
    public ?string $linkLogin = null;
    public ?string $mac = null;
    public ?string $ip = null;
    public ?string $router = null;


    public function mount()
    {
        $this->linkLogin = request()->get('link-login')
            ?? request()->get('link-login-only')
            ?? request()->get('link_login')
            ?? request()->get('link-orig');

        $this->mac = request()->get('mac');
        $this->ip = request()->get('ip');
        $this->router = request()->get('router');
    }

    public function updatedPhone()
    {
        $this->isVoucher = (bool) preg_match('/^VCH-[A-Z0-9]+$/i', trim($this->phone));
        $this->error = '';
    }

    // ── Step: phone ──────────────────────────────────────────────

    public function sendOtp()
    {
        $input = trim($this->phone);

        // Voucher flow — validate then ask for phone
        if (Voucher::isVoucherCode($input)) {
            $this->isVoucher = true;
            $this->voucherCode = strtoupper(trim($input));
            $this->validateVoucherAndAskPhone();
            return;
        }

        $this->isVoucher = false;
        $this->otpPurpose = 'login';

        $this->sendOtpToPhone($input);
    }

    // ── Step: voucher_phone (phone input after voucher validation) ─

    public function sendVoucherOtp()
    {
        $this->otpPurpose = 'voucher';
        $this->sendOtpToPhone($this->phone);
    }

    // ── Shared OTP sender ────────────────────────────────────────

    private function sendOtpToPhone(string $input): void
    {
        $digits = preg_replace('/[\s\-\(\)]/', '', $input);

        if (empty($digits) || ! preg_match('/^\+?[\d]{7,15}$/', $digits)) {
            $this->error = 'Please enter a valid phone number.';
            return;
        }

        try {
            $normalized = User::normalizePhone($digits);

            $wa = new WhatsAppService();

            if (! $wa->checkOtpRateLimit($normalized)) {
                $this->error = 'Too many attempts. Please wait a few minutes.';
                return;
            }

            $code = $wa->sendOtp($normalized);

            if (! $code) {
                $this->error = 'Could not send OTP. Please try again.';
                return;
            }

            $this->phone = $normalized;
            $this->step = 'otp';
            $this->error = '';
            $this->success = 'A verification code has been sent to your WhatsApp.';
            $this->resendCountdown = 60;
        } catch (\Throwable $e) {
            Log::error('CaptiveAuth sendOtp failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    // ── Step: otp ────────────────────────────────────────────────

    public function verifyOtp()
    {
        $code = trim($this->otp);

        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            $this->error = 'Please enter a valid 6-digit code.';
            return;
        }

        $wa = new WhatsAppService();

        if (! $wa->verifyOtp($this->phone, $code)) {
            $this->error = 'Invalid or expired code. Please try again.';
            return;
        }

        // Find user by last 10 digits — matches every possible format variation:
        // 08012345678 / +2348012345678 / 2348012345678 / 8012345678 / 08012345678, etc.
        $last10 = substr(preg_replace('/\D/', '', $this->phone), -10);

        $user = User::where('phone', 'like', '%' . $last10)->first();

        // Migrate stale phone format to canonical +234... so the LIKE is never needed again
        if ($user && $user->getRawOriginal('phone') !== $this->phone) {
            $user->phone = $this->phone; // goes through setPhoneAttribute normalizer
            $user->saveQuietly();
        }

        if (! $user) {
            $username = 'user_' . substr(preg_replace('/\D/', '', $this->phone), -10);
            $base = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $counter;
                $counter++;
            }

            $user = User::create([
                'name'              => null,
                'username'          => $username,
                'email'             => null,
                'phone'             => $this->phone,
                'password'          => null,
                'radius_password'   => Str::random(12),
                'phone_verified_at' => now(),
                'connection_status' => 'active',
            ]);
        } else {
            if (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        if ($this->otpPurpose === 'voucher' && $this->voucherCode) {
            $this->activateVoucherForUser($user);
        } else {
            $this->completeLogin($user);
        }
    }

    public function resendOtp()
    {
        try {
            $wa = new WhatsAppService();

            if (! $wa->checkOtpRateLimit($this->phone)) {
                $this->error = 'Too many attempts. Please wait a few minutes.';
                return;
            }

            $code = $wa->sendOtp($this->phone);

            if (! $code) {
                $this->error = 'Could not resend. Please try again.';
                return;
            }

            $this->success = 'A new code has been sent.';
            $this->error = '';
            $this->resendCountdown = 60;
        } catch (\Throwable $e) {
            Log::error('CaptiveAuth resendOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    // ── Navigation ───────────────────────────────────────────────

    public function goBack()
    {
        if ($this->otpPurpose === 'voucher') {
            $this->step = 'voucher_phone';
        } else {
            $this->step = 'phone';
        }
        $this->otp = '';
        $this->error = '';
        $this->success = '';
    }

    public function switchToEmail()
    {
        $this->step = 'email';
        $this->error = '';
        $this->success = '';
    }

    public function switchToPhone()
    {
        $this->step = 'phone';
        $this->error = '';
        $this->success = '';
    }

    // ── Email login ──────────────────────────────────────────────

    public function emailLogin()
    {
        $login = trim($this->email);
        $password = $this->password;

        if (empty($login) || empty($password)) {
            $this->error = 'Please enter your email/username and password.';
            return;
        }

        try {
            $authenticated = false;

            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $authenticated = Auth::attempt(['email' => $login, 'password' => $password], true);
            }

            if (! $authenticated && is_numeric($login)) {
                $authenticated = Auth::attempt(['phone' => $login, 'password' => $password], true);
            }

            if (! $authenticated) {
                $authenticated = Auth::attempt(['username' => $login, 'password' => $password], true);
            }

            if (! $authenticated) {
                $this->error = 'Invalid credentials. Please try again.';
                return;
            }

            request()->session()->regenerate();
            $this->completeLogin(Auth::user());
        } catch (\Throwable $e) {
            Log::error('CaptiveAuth emailLogin failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
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

    // ── Post-login bridge ────────────────────────────────────────

    private function completeLogin($user): void
    {
        if ($this->mac) {
            session(['current_device_mac' => $this->mac]);

            try {
                Device::upsertFromLogin(
                    $user,
                    $this->mac,
                    $this->router,
                    $this->ip ?? request()->ip(),
                    request()->userAgent()
                );
            } catch (\Throwable $e) {
                Log::warning('Device upsert failed during captive auth: ' . $e->getMessage());
            }
        }

        if ($this->router) {
            session(['current_router' => $this->router]);
        }

        if ($this->linkLogin) {
            session(['captive_link_login' => $this->linkLogin]);
            session(['captive_mac' => $this->mac]);
            session(['captive_ip' => $this->ip]);
            session(['captive_router' => $this->router]);
        }

        $subscriptionService = new \App\Services\SubscriptionService();

        if ($subscriptionService->canConnectToHotspot($user) && $this->linkLogin) {
            $rad = RadCheck::where('username', $user->username)
                ->where('attribute', 'Cleartext-Password')
                ->first();
            $radPassword = $rad?->value ?? $user->radius_password;

            if ($radPassword) {
                session(['bridge_completed' => true]);
                $this->bridgeToRouter($user->username, $radPassword, $this->linkLogin, route('dashboard'));
                return;
            }
        }

        $this->redirect(route('dashboard'));
    }

    // ── Voucher: validate and ask for phone ──────────────────────

    private function validateVoucherAndAskPhone(): void
    {
        $voucher = Voucher::findValid($this->voucherCode);

        if (! $voucher) {
            $this->error = 'This voucher is invalid, expired, or already used.';
            return;
        }

        $familyHead = $voucher->creator;

        if ($familyHead) {
            $subscriptionService = new \App\Services\SubscriptionService();
            if (! $subscriptionService->canConnectToHotspot($familyHead)) {
                $this->error = "The voucher owner's plan has expired or run out of data.";
                return;
            }
        }

        // Voucher is valid — ask for phone number
        $this->step = 'voucher_phone';
        $this->error = '';
        $this->phone = '';
    }

    // ── Voucher: activate for verified user ──────────────────────

    private function activateVoucherForUser($user): void
    {
        $voucher = Voucher::findValid($this->voucherCode);

        if (! $voucher) {
            $this->error = 'This voucher is no longer available.';
            $this->step = 'phone';
            return;
        }

        $familyHead = $voucher->creator;

        // Check if this user already redeemed this voucher (same phone = same slot)
        $alreadyLinked = Device::where('user_id', $user->id)
            ->where('meta->voucher_code', $this->voucherCode)
            ->exists();

        if (! $alreadyLinked) {
            // Consume a slot for this new person
            $voucher->consume();
        }

        // Link user under the voucher creator if they're a family head (not admin)
        if ($familyHead && ! $familyHead->isAdmin() && ! $user->parent_id) {
            $user->updateQuietly(['parent_id' => $familyHead->id]);
        }

        // RADIUS uses the user's username, not the voucher code
        $radUsername = $user->username;
        $radPassword = $user->radius_password;

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radPassword]
        );

        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => (string) ($voucher->max_uses)]
        );

        // Expiration: creator's plan_expiry or voucher's own
        $expirationDate = $familyHead?->plan_expiry ?? $voucher->expires_at;
        if ($expirationDate) {
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $expirationDate->format('d M Y H:i')]
            );
        }

        // Speed limits
        $fallbackPlan = $voucher->plan ?? $familyHead?->plan;
        $uploadKbps   = $voucher->speed_limit_upload   ?? $fallbackPlan?->speed_limit_upload;
        $downloadKbps = $voucher->speed_limit_download ?? $fallbackPlan?->speed_limit_download;
        if ($uploadKbps || $downloadKbps) {
            \App\Models\RadReply::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => ($uploadKbps ?? 0) . 'k/' . ($downloadKbps ?? 0) . 'k']
            );
        }

        // Data cap
        if (! $voucher->is_unlimited) {
            $refPlan = $voucher->plan ?? $familyHead?->plan;
            $planLimitMb = null;
            if ($refPlan && $refPlan->limit_unit !== 'Unlimited' && $refPlan->data_limit) {
                $planLimitMb = $refPlan->limit_unit === 'GB'
                    ? (int) ($refPlan->data_limit * 1024)
                    : (int) $refPlan->data_limit;
            }
            $limitMb = $voucher->data_limit_mb ?? $planLimitMb;
            if ($limitMb) {
                \App\Models\RadReply::updateOrCreate(
                    ['username' => $radUsername, 'attribute' => 'Mikrotik-Total-Limit'],
                    ['op' => ':=', 'value' => (string) ($limitMb * 1048576)]
                );
            }
        } else {
            \App\Models\RadReply::where('username', $radUsername)->where('attribute', 'Mikrotik-Total-Limit')->delete();
        }

        // Save device MAC linked to user (not anonymous)
        if ($this->mac) {
            Device::updateOrCreate(
                ['mac' => strtoupper($this->mac)],
                [
                    'user_id'      => $user->id,
                    'router_id'    => $voucher->router_id,
                    'ip'           => $this->ip ?? request()->ip(),
                    'user_agent'   => request()->userAgent(),
                    'first_seen'   => now(),
                    'last_seen'    => now(),
                    'is_connected' => true,
                    'meta'         => ['voucher_code' => $this->voucherCode],
                ]
            );
        }

        // Bridge to MikroTik
        $bridgeUrl = $this->linkLogin;
        if (! $bridgeUrl) {
            $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'login.wifi';
            $cleanHost = preg_replace('#^https?://#i', '', $gateway);
            $cleanHost = preg_replace('#/login$#', '', rtrim($cleanHost, '/'));
            $bridgeUrl = 'http://' . $cleanHost . '/login';
        }

        session(['bridge_completed' => true]);
        $this->bridgeToRouter($radUsername, $radPassword, $bridgeUrl, route('voucher.success', ['code' => $this->voucherCode]));
    }

    public function render()
    {
        return view('livewire.captive-auth');
    }
}
