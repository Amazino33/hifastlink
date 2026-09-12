<?php

namespace App\Http\Livewire;

use App\Models\Device;
use App\Models\RadCheck;
use App\Models\User;
use App\Services\FreeTrialService;
use App\Services\PlanSyncService;
use App\Services\WhatsAppService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class PhoneOtpLogin extends Component
{
    public string $phone = '';
    public string $otp   = '';
    public string $step  = 'phone'; // phone | otp
    public string $error   = '';
    public string $success = '';
    public int    $resendCountdown = 0;

    // Props — set at mount
    public string $mode      = 'login';
    public string $bonus     = '';
    public string $router    = '';
    public string $linkLogin = '';
    public string $mac       = '';
    public string $ip        = '';

    public function mount(
        string $mode = 'login',
        string $bonus = '',
        string $router = '',
        string $linkLogin = '',
        string $mac = '',
        string $ip = ''
    ): void {
        $this->mode      = $mode;
        $this->bonus     = $bonus;
        $this->router    = $router;
        $this->linkLogin = $linkLogin ?: (request()->get('link-login') ?? request()->get('link-login-only') ?? request()->get('link_login') ?? '');
        $this->mac       = $mac ?: (request()->get('mac') ?? '');
        $this->ip        = $ip ?: (request()->get('ip') ?? request()->ip());
    }

    public function updatedPhone(): void
    {
        $this->error = '';
    }

    public function sendOtp(): void
    {
        $digits = preg_replace('/[\s\-\(\)]/', '', trim($this->phone));

        if (empty($digits) || ! preg_match('/^\+?[\d]{7,15}$/', $digits)) {
            $this->error = 'Please enter a valid phone number (e.g. 08012345678).';
            return;
        }

        try {
            $normalized = User::normalizePhone($digits);
            $wa         = new WhatsAppService();

            if (! $wa->checkOtpRateLimit($normalized)) {
                $this->error = 'Too many attempts. Please wait a few minutes.';
                return;
            }

            $code = $wa->sendOtp($normalized);

            if (! $code) {
                $this->error = 'Could not send WhatsApp code. Please check the number and try again.';
                return;
            }

            $this->phone           = $normalized;
            $this->step            = 'otp';
            $this->error           = '';
            $this->success         = 'A 6-digit verification code has been sent to your WhatsApp.';
            $this->resendCountdown = 60;
        } catch (\Throwable $e) {
            Log::error('PhoneOtpLogin sendOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    public function resendOtp(): void
    {
        if ($this->resendCountdown > 0) {
            return;
        }
        $this->step = 'phone';
        $this->sendOtp();
    }

    public function verifyOtp(): void
    {
        $code = trim($this->otp);

        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            $this->error = 'Please enter a valid 6-digit code.';
            return;
        }

        try {
            $wa = new WhatsAppService();

            if (! $wa->verifyOtp($this->phone, $code)) {
                $this->error = 'Invalid or expired code. Please try again.';
                return;
            }

            $last10 = substr(preg_replace('/\D/', '', $this->phone), -10);
            $user   = User::where('phone', $this->phone)->first();

            if (! $user) {
                $candidate = User::where('phone', 'like', '%' . $last10)->first();
                if ($candidate) {
                    try {
                        $candidate->phone = $this->phone;
                        $candidate->saveQuietly();
                        $user = $candidate;
                    } catch (\Illuminate\Database\QueryException) {
                        $user = User::where('phone', $this->phone)->first();
                    }
                }
            }

            if ($user) {
                // Apply free trial if new user hasn't claimed it yet and toggle is on
                FreeTrialService::apply($user, $this->router ?: null);
                PlanSyncService::syncUserPlan($user);
                $this->doLogin($user);
                return;
            }

            // New user — create account directly without password friction
            $this->createAccountFromVerifiedPhone($last10);
        } catch (\Throwable $e) {
            Log::error('PhoneOtpLogin verifyOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong verifying your code. Please try again.';
        }
    }

    public function createAccountFromVerifiedPhone(string $last10): void
    {
        try {
            $username       = $this->generateUsername($last10);
            $randomPassword = Str::random(16);

            $user = User::create([
                'name'              => 'User ' . substr($last10, -4),
                'username'          => $username,
                'phone'             => $this->phone,
                'phone_verified_at' => now(),
                'password'          => Hash::make($randomPassword),
                'radius_password'   => $randomPassword,
                'data_limit'        => 1000000000,
                'connection_status' => 'active',
            ]);

            event(new Registered($user));

            // Apply free trial if enabled
            FreeTrialService::apply($user, $this->router ?: null);

            // Push credentials to radcheck immediately so FreeRADIUS can authenticate them
            PlanSyncService::syncUserPlan($user);

            $this->doLogin($user);
        } catch (\Throwable $e) {
            Log::error('PhoneOtpLogin createAccount failed: ' . $e->getMessage());
            $this->error = 'Something went wrong creating your account. Please try again.';
        }
    }

    public function back(): void
    {
        $this->step    = 'phone';
        $this->otp     = '';
        $this->error   = '';
        $this->success = '';
    }

    private function doLogin(User $user): void
    {
        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        Auth::login($user, remember: true);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

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
                Log::warning('PhoneOtpLogin: device upsert failed: ' . $e->getMessage());
            }
        }

        if ($this->linkLogin) {
            $rad = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
            $password = $rad?->value ?? $user->radius_password;

            if ($password) {
                if (request()->hasSession()) {
                    session([
                        'bridge_username'   => $user->username,
                        'bridge_password'   => $password,
                        'bridge_link_login' => $this->linkLogin,
                        'bridge_link_orig'  => route('app.home'),
                        'bridge_mac'        => $this->mac,
                        'bridge_ip'         => $this->ip,
                        'bridge_router'     => $this->router,
                    ]);
                }

                $this->redirect(route('captive.bridge'));
                return;
            }
        }

        $this->redirectIntended(route('app.home'));
    }

    private function generateUsername(string $last10): string
    {
        $base     = 'user_' . $last10;
        $username = $base;
        $i        = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }
        return $username;
    }

    public function render()
    {
        return view('livewire.phone-otp-login');
    }
}
