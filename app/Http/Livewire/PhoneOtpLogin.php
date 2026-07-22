<?php

namespace App\Http\Livewire;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class PhoneOtpLogin extends Component
{
    public string $phone = '';
    public string $otp   = '';
    public string $step  = 'phone'; // phone | otp
    public string $error   = '';
    public string $success = '';
    public int    $resendCountdown = 0;

    public function updatedPhone(): void
    {
        $this->error = '';
    }

    public function sendOtp(): void
    {
        $digits = preg_replace('/[\s\-\(\)]/', '', trim($this->phone));

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
                $this->error = 'No account found for this number. Please register first.';
                return;
            }

            if (! $user->phone_verified_at) {
                $user->update(['phone_verified_at' => now()]);
            }

            Auth::login($user, remember: true);
            request()->session()->regenerate();

            $this->redirectIntended(route('dashboard'));
        } catch (\Throwable $e) {
            Log::error('PhoneOtpLogin verifyOtp failed: ' . $e->getMessage());
            $this->error = 'Something went wrong. Please try again.';
        }
    }

    public function back(): void
    {
        $this->step    = 'phone';
        $this->otp     = '';
        $this->error   = '';
        $this->success = '';
    }

    public function render()
    {
        return view('livewire.phone-otp-login');
    }
}
