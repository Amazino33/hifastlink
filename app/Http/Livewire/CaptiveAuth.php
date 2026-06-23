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
    public string $step = 'phone'; // phone | otp | done
    public string $phone = '';
    public string $otp = '';
    public string $voucherCode = '';
    public string $error = '';
    public string $success = '';
    public bool $isVoucher = false;
    public int $resendCountdown = 0;

    // Captive portal params (persisted across Livewire requests)
    public ?string $linkLogin = null;
    public ?string $mac = null;
    public ?string $ip = null;
    public ?string $router = null;

    // Bridge data (set after successful auth when user has a plan)
    public ?string $bridgeUsername = null;
    public ?string $bridgePassword = null;
    public ?string $bridgeLinkLogin = null;
    public ?string $bridgeLinkOrig = null;

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

    public function sendOtp()
    {
        $input = trim($this->phone);

        // Voucher flow — redirect to existing voucher handler
        if (Voucher::isVoucherCode($input)) {
            $this->isVoucher = true;
            $this->voucherCode = $input;
            $this->handleVoucherDirect();
            return;
        }

        $this->isVoucher = false;

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

        // OTP verified — find or create user
        $user = User::where('phone', $this->phone)->first();

        if (! $user) {
            $username = 'user_' . substr(preg_replace('/\D/', '', $this->phone), -10);

            // Ensure unique username
            $base = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $counter;
                $counter++;
            }

            $radiusPassword = Str::random(12);

            $user = User::create([
                'name'              => null,
                'username'          => $username,
                'email'             => null,
                'phone'             => $this->phone,
                'password'          => null,
                'radius_password'   => $radiusPassword,
                'phone_verified_at' => now(),
                'connection_status' => 'active',
            ]);
        } else {
            if (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }
        }

        // Log in
        Auth::login($user, remember: true);
        request()->session()->regenerate();

        // Save device MAC
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

        // Store captive portal params in session for post-payment bridging
        if ($this->linkLogin) {
            session(['captive_link_login' => $this->linkLogin]);
            session(['captive_mac' => $this->mac]);
            session(['captive_ip' => $this->ip]);
            session(['captive_router' => $this->router]);
        }

        // Check if user can connect (has active plan)
        $subscriptionService = new \App\Services\SubscriptionService();

        if ($subscriptionService->canConnectToHotspot($user) && $this->linkLogin) {
            $rad = RadCheck::where('username', $user->username)
                ->where('attribute', 'Cleartext-Password')
                ->first();
            $password = $rad?->value ?? $user->radius_password;

            if ($password) {
                $this->bridgeUsername = $user->username;
                $this->bridgePassword = $password;
                $this->bridgeLinkLogin = $this->linkLogin;
                $this->bridgeLinkOrig = route('dashboard');
                $this->step = 'done';
                return;
            }
        }

        // No plan or no captive portal — redirect to dashboard
        $this->redirect(route('dashboard'));
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

    public function goBack()
    {
        $this->step = 'phone';
        $this->otp = '';
        $this->error = '';
        $this->success = '';
    }

    private function handleVoucherDirect()
    {
        $input = strtoupper(trim($this->voucherCode));

        $voucher = Voucher::findValid($input);

        if (! $voucher) {
            $this->error = 'This voucher is invalid, expired, or already used.';
            return;
        }

        $familyHead = $voucher->creator;

        // Admin-created vouchers have no creator — they're standalone and always valid
        if ($familyHead) {
            $subscriptionService = new \App\Services\SubscriptionService();
            if (! $subscriptionService->canConnectToHotspot($familyHead)) {
                $this->error = "The voucher owner's plan has expired or run out of data.";
                return;
            }
        }

        // Consume and set up RADIUS
        $voucher->consume();

        RadCheck::updateOrCreate(
            ['username' => $input, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $input]
        );

        RadCheck::updateOrCreate(
            ['username' => $input, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => (string) $voucher->max_uses]
        );

        if ($voucher->expires_at) {
            RadCheck::updateOrCreate(
                ['username' => $input, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $voucher->expires_at->format('d M Y H:i')]
            );
        }

        // Speed limits: voucher → linked plan → creator's plan
        $fallbackPlan = $voucher->plan ?? $familyHead?->plan;
        $uploadKbps   = $voucher->speed_limit_upload   ?? $fallbackPlan?->speed_limit_upload;
        $downloadKbps = $voucher->speed_limit_download ?? $fallbackPlan?->speed_limit_download;
        if ($uploadKbps || $downloadKbps) {
            \App\Models\RadReply::updateOrCreate(
                ['username' => $input, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => ($uploadKbps ?? 0) . 'k/' . ($downloadKbps ?? 0) . 'k']
            );
        }

        // Data cap
        if (! $voucher->is_unlimited) {
            $planLimitMb = null;
            $refPlan = $voucher->plan ?? $familyHead?->plan;
            if ($refPlan && $refPlan->limit_unit !== 'Unlimited' && $refPlan->data_limit) {
                $planLimitMb = $refPlan->limit_unit === 'GB'
                    ? (int) ($refPlan->data_limit * 1024)
                    : (int) $refPlan->data_limit;
            }
            $limitMb = $voucher->data_limit_mb ?? $planLimitMb;
            if ($limitMb) {
                \App\Models\RadReply::updateOrCreate(
                    ['username' => $input, 'attribute' => 'Mikrotik-Total-Limit'],
                    ['op' => ':=', 'value' => (string) ($limitMb * 1048576)]
                );
            } else {
                \App\Models\RadReply::where('username', $input)->where('attribute', 'Mikrotik-Total-Limit')->delete();
            }
            RadCheck::where('username', $input)->where('attribute', 'Mikrotik-Total-Limit')->delete();
        } else {
            RadCheck::where('username', $input)->where('attribute', 'Mikrotik-Total-Limit')->delete();
            \App\Models\RadReply::where('username', $input)->where('attribute', 'Mikrotik-Total-Limit')->delete();
        }

        // Store device MAC for voucher
        if ($this->mac) {
            Device::updateOrCreate(
                ['mac' => strtoupper($this->mac)],
                [
                    'user_id'      => null,
                    'router_id'    => $voucher->router_id,
                    'ip'           => $this->ip ?? request()->ip(),
                    'user_agent'   => request()->userAgent(),
                    'first_seen'   => now(),
                    'last_seen'    => now(),
                    'is_connected' => true,
                    'meta'         => ['voucher_code' => $input],
                ]
            );
        }

        if ($this->linkLogin) {
            $this->bridgeUsername = $input;
            $this->bridgePassword = $input;
            $this->bridgeLinkLogin = $this->linkLogin;
            $this->bridgeLinkOrig = route('voucher.success', ['code' => $input]);
            $this->step = 'done';
        } else {
            $this->redirect(route('voucher.success', ['code' => $input]));
        }
    }

    public function render()
    {
        return view('livewire.captive-auth');
    }
}
