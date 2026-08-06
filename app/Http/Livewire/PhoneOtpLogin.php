<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Services\FreeTrialService;
use App\Services\WhatsAppService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class PhoneOtpLogin extends Component
{
    public string $phone = '';
    public string $otp   = '';
    public string $step  = 'phone'; // phone | otp | exists | password
    public string $error   = '';
    public string $success = '';
    public int    $resendCountdown = 0;

    public string $password             = '';
    public string $passwordConfirmation = '';
    public int    $existingUserId       = 0;

    // Props — set at mount
    public string $mode   = 'login';
    public string $bonus  = '';
    public string $router = '';

    public function mount(string $mode = 'login', string $bonus = '', string $router = ''): void
    {
        $this->mode   = $mode;
        $this->bonus  = $bonus;
        $this->router = $router;
    }

    public function updatedPhone(): void { $this->error = ''; }

    public function sendOtp(): void
    {
        $digits = preg_replace('/[\s\-\(\)]/', '', trim($this->phone));

        if (empty($digits) || ! preg_match('/^\+?[\d]{7,15}$/', $digits)) {
            $this->error = 'Please enter a valid phone number.';
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
                $this->error = 'Could not send OTP. Please try again.';
                return;
            }

            $this->phone           = $normalized;
            $this->step            = 'otp';
            $this->error           = '';
            $this->success         = 'A verification code has been sent to your WhatsApp.';
            $this->resendCountdown = 60;
        } catch (\Throwable $e) {
            Log::error('PhoneOtpLogin sendOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    public function resendOtp(): void
    {
        if ($this->resendCountdown > 0) return;
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
                // Existing user — show the "no trial" screen; otherwise just log in
                if ($this->bonus === 'free_trial') {
                    $this->existingUserId = $user->id;
                    $this->step           = 'exists';
                    $this->error          = '';
                    return;
                }

                $this->doLogin($user);
                return;
            }

            // New user — proceed to password creation
            $this->step    = 'password';
            $this->error   = '';
            $this->success = 'Phone verified! Set a password to complete your account.';
        } catch (\Throwable $e) {
            Log::error('PhoneOtpLogin verifyOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    public function loginExisting(): void
    {
        $user = User::find($this->existingUserId);

        if (! $user) {
            $this->error = 'Account not found. Please try again.';
            $this->step  = 'phone';
            return;
        }

        $this->doLogin($user);
    }

    public function createAccount(): void
    {
        $this->validate([
            'password'             => ['required', Password::defaults()],
            'passwordConfirmation' => ['required', 'same:password'],
        ], [
            'passwordConfirmation.required' => 'Please confirm your password.',
            'passwordConfirmation.same'     => 'Passwords do not match.',
        ]);

        try {
            $last10   = substr(preg_replace('/\D/', '', $this->phone), -10);
            $username = $this->generateUsername($last10);

            $user = User::create([
                'name'              => 'User',
                'username'          => $username,
                'phone'             => $this->phone,
                'phone_verified_at' => now(),
                'password'          => Hash::make($this->password),
                'radius_password'   => $this->password,
                'data_limit'        => 1000000000,
                'connection_status' => 'active',
            ]);

            event(new Registered($user));

            if ($this->bonus === 'free_trial') {
                FreeTrialService::apply($user, $this->router ?: null);
            }

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
        request()->session()->regenerate();

        $this->redirectIntended(route('dashboard'));
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
