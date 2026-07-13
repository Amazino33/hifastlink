<?php

namespace App\Http\Livewire;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\RadCheck;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class PharmacyVoucher extends Component
{
    public string $step = 'invoice'; // invoice | phone | otp | success

    public string $invoiceNumber = '';
    public string $phone         = '';
    public string $otp           = '';
    public string $error         = '';
    public string $success       = '';
    public int    $resendCountdown = 0;

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

    // ── Step: invoice ────────────────────────────────────────────

    public function validateInvoice(): void
    {
        $this->validate(['invoiceNumber' => 'required|string|max:100']);

        $apiUrl = AppSetting::get('basmelcare_api_url', '');
        $apiKey = AppSetting::get('basmelcare_api_key', '');

        if (! $apiUrl || ! $apiKey) {
            $this->error = 'Pharmacy integration is not configured. Please contact support.';
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-API-Key' => $apiKey])
                ->post($apiUrl, ['invoice_number' => trim($this->invoiceNumber)]);

            if (! $response->successful() || ! $response->json('valid')) {
                $this->error = $response->json('message', 'Invalid or already used receipt number.');
                return;
            }

            $this->expiresAt     = $response->json('expires_at');
            $this->validityHours = $response->json('validity_hours', 24);
            $this->step  = 'phone';
            $this->error = '';
        } catch (\Throwable $e) {
            Log::error('[PharmacyVoucher] API call failed: ' . $e->getMessage());
            $this->error = 'Could not reach BasmelCare. Please try again.';
        }
    }

    // ── Step: phone ──────────────────────────────────────────────

    public function sendOtp(): void
    {
        $this->validate(['phone' => 'required|string|max:20']);

        $digits = preg_replace('/[\s\-\(\)]/', '', $this->phone);

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

            $this->phone          = $normalized;
            $this->step           = 'otp';
            $this->error          = '';
            $this->success        = 'A verification code has been sent to your WhatsApp.';
            $this->resendCountdown = 60;
        } catch (\Throwable $e) {
            Log::error('[PharmacyVoucher] sendOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    public function resendOtp(): void
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

            $this->success        = 'A new code has been sent.';
            $this->error          = '';
            $this->resendCountdown = 60;
        } catch (\Throwable $e) {
            Log::error('[PharmacyVoucher] resendOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong.';
        }
    }

    // ── Step: otp ────────────────────────────────────────────────

    public function verifyOtp(): void
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

        $last10 = substr(preg_replace('/\D/', '', $this->phone), -10);

        $user = User::where('phone', $this->phone)->first();

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

        if (! $user) {
            $username = 'user_' . $last10;
            $base     = $username;
            $counter  = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $counter++;
            }

            try {
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
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $user = User::where('username', $username)->first()
                    ?? User::where('phone', 'like', '%' . $last10)->first();

                if (! $user) {
                    $this->error = 'Something went wrong. Please try again.';
                    return;
                }
            }
        } elseif (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        Auth::login($user, remember: true);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->activateForUser($user);
    }

    // ── Activate internet access ─────────────────────────────────

    private function activateForUser(User $user): void
    {
        $radUsername = $user->username;
        $radPassword = $user->radius_password;

        // Ensure RADIUS credentials exist
        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $radPassword]
        );

        // One active session at a time
        RadCheck::updateOrCreate(
            ['username' => $radUsername, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => '1']
        );

        // Expiration — use the timestamp from BasmelCare
        if ($this->expiresAt) {
            $expiry = \Carbon\Carbon::parse($this->expiresAt);
            RadCheck::updateOrCreate(
                ['username' => $radUsername, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $expiry->format('d M Y H:i')]
            );
        }

        // Track device if on captive portal
        if ($this->mac) {
            Device::updateOrCreate(
                ['mac' => strtoupper($this->mac)],
                [
                    'user_id'      => $user->id,
                    'ip'           => $this->ip ?? request()->ip(),
                    'user_agent'   => request()->userAgent(),
                    'first_seen'   => now(),
                    'last_seen'    => now(),
                    'is_connected' => true,
                    'meta'         => ['pharmacy_invoice' => $this->invoiceNumber],
                ]
            );
        }

        // Bridge to MikroTik if on captive portal
        if ($this->linkLogin) {
            session([
                'bridge_username'   => $radUsername,
                'bridge_password'   => $radPassword,
                'bridge_link_login' => $this->linkLogin,
                'bridge_link_orig'  => route('home'),
                'bridge_mac'        => $this->mac,
                'bridge_ip'         => $this->ip,
                'bridge_router'     => $this->router,
                'bridge_completed'  => true,
            ]);

            $this->redirect(route('captive.bridge'));
            return;
        }

        // Direct access (not on captive portal) — show success
        $this->step    = 'success';
        $this->error   = '';
        $this->success = '';
    }

    public function goBack(): void
    {
        $this->step    = $this->step === 'otp' ? 'phone' : 'invoice';
        $this->otp     = '';
        $this->error   = '';
        $this->success = '';
    }

    public function render()
    {
        return view('livewire.pharmacy-voucher');
    }
}
