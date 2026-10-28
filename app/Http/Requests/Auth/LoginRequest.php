<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $login = trim($this->input('login', ''));
        $isVoucher = Voucher::isVoucherCode($login);

        return [
            'login'    => ['required', 'string'],
            'password' => $isVoucher ? ['nullable', 'string'] : ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $rawLogin = trim((string) $this->input('login', ''));
        $password = (string) $this->input('password', '');
        $remember = $this->boolean('remember');

        $authenticated = false;

        // 1. Try email
        if (filter_var($rawLogin, FILTER_VALIDATE_EMAIL)) {
            $authenticated = Auth::attempt(['email' => strtolower($rawLogin), 'password' => $password], $remember)
                || Auth::attempt(['email' => $rawLogin, 'password' => $password], $remember);
        }

        // 2. Try phone number (both normalized E.164 and raw/digits format)
        if (! $authenticated) {
            $digits = preg_replace('/\D/', '', $rawLogin);
            if (strlen($digits) >= 7) {
                $normalizedPhone = \App\Models\User::normalizePhone($rawLogin);
                $last10 = substr($digits, -10);

                // Attempt standard auth with normalized phone and raw phone
                $authenticated = Auth::attempt(['phone' => $normalizedPhone, 'password' => $password], $remember)
                    || Auth::attempt(['phone' => $rawLogin, 'password' => $password], $remember);

                // If not authenticated via standard attempt, check candidate user
                if (! $authenticated) {
                    $candidate = \App\Models\User::where('phone', $normalizedPhone)
                        ->orWhere('phone', $rawLogin)
                        ->orWhere('phone', 'like', '%' . $last10)
                        ->first();

                    if ($candidate) {
                        if (\Illuminate\Support\Facades\Hash::check($password, $candidate->password)
                            || (! empty($candidate->radius_password) && $candidate->radius_password === $password)) {
                            Auth::login($candidate, $remember);
                            $authenticated = true;
                        }
                    }
                }
            }
        }

        // 3. Try username
        if (! $authenticated) {
            $authenticated = Auth::attempt(['username' => $rawLogin, 'password' => $password], $remember);

            // Also check radius_password if user exists by username
            if (! $authenticated) {
                $candidate = \App\Models\User::where('username', $rawLogin)->first();
                if ($candidate && ! empty($candidate->radius_password) && $candidate->radius_password === $password) {
                    Auth::login($candidate, $remember);
                    $authenticated = true;
                }
            }
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey());

            // Provide clear feedback if the account exists but was created via WhatsApp without a password
            $digits = preg_replace('/\D/', '', $rawLogin);
            $existingUser = null;
            if (filter_var($rawLogin, FILTER_VALIDATE_EMAIL)) {
                $existingUser = \App\Models\User::where('email', strtolower($rawLogin))->first();
            } elseif (strlen($digits) >= 7) {
                $normalizedPhone = \App\Models\User::normalizePhone($rawLogin);
                $existingUser = \App\Models\User::where('phone', $normalizedPhone)
                    ->orWhere('phone', 'like', '%' . substr($digits, -10))
                    ->first();
            } else {
                $existingUser = \App\Models\User::where('username', $rawLogin)->first();
            }

            if ($existingUser && ! empty($existingUser->phone) && str_starts_with($existingUser->username ?? '', 'user_') && empty($existingUser->email)) {
                throw ValidationException::withMessages([
                    'login' => 'This account was set up via WhatsApp. Please sign in using the WhatsApp tab above, or use "Forgot?" to set a PIN.',
                ]);
            }

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
