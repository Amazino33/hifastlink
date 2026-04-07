<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\RadCheck;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|Response
    {
        $linkLogin = request()->get('link-login')
                  ?? request()->get('link-login-only')
                  ?? request()->get('link_login')
                  ?? request()->get('link-orig');

        $mac = request()->get('mac');

        // ── Layer 1: MAC-based auto-reconnect ─────────────────────────
        // MikroTik always passes the device MAC in the captive portal URL.
        // If we recognise that MAC and the owner has an active subscription,
        // bridge them silently — no form, no password prompt.
        if ($linkLogin && $mac) {
            $device = \App\Models\Device::where('mac', $mac)
                ->with('user')
                ->first();

            if ($device?->user) {
                $user                = $device->user;
                $subscriptionService = new \App\Services\SubscriptionService();

                if ($subscriptionService->canConnectToHotspot($user)) {
                    $rad      = RadCheck::where('username', $user->username)
                                        ->where('attribute', 'Cleartext-Password')
                                        ->first();
                    $password = $rad?->value ?? $user->radius_password;

                    if ($password) {
                        Auth::login($user, remember: true);
                        request()->session()->regenerate();

                        try {
                            \App\Models\Device::upsertFromLogin(
                                $user,
                                $mac,
                                request()->get('router'),
                                request()->get('ip') ?? request()->ip(),
                                request()->userAgent()
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Device upsert failed during MAC auto-reconnect: ' . $e->getMessage());
                        }

                        return response()->view('hotspot.redirect_to_router', [
                            'username'   => $user->username,
                            'password'   => $password,
                            'link_login' => $linkLogin,
                            'link_orig'  => route('dashboard'),
                            'mac'        => $mac,
                            'ip'         => request()->get('ip'),
                            'router'     => request()->get('router'),
                        ]);
                    }
                }
            }
        }

        // ── Layer 2: Active Laravel session ───────────────────────────
        // Session cookie is still alive but MikroTik hotspot session
        // dropped (e.g. router reboot). Re-bridge without showing form.
        if (auth()->check() && $linkLogin) {
            $user                = auth()->user();
            $subscriptionService = new \App\Services\SubscriptionService();

            if ($subscriptionService->canConnectToHotspot($user)) {
                $rad      = RadCheck::where('username', $user->username)
                                    ->where('attribute', 'Cleartext-Password')
                                    ->first();
                $password = $rad?->value ?? $user->radius_password;

                if ($password) {
                    return response()->view('hotspot.redirect_to_router', [
                        'username'   => $user->username,
                        'password'   => $password,
                        'link_login' => $linkLogin,
                        'link_orig'  => route('dashboard'),
                        'mac'        => $mac,
                        'ip'         => request()->get('ip'),
                        'router'     => request()->get('router'),
                    ]);
                }
            }
        }

        // ── Layer 3: Unknown device, no session — show login form ──────
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse|Response
    {
        $loginInput = trim($request->input('login', ''));

        // ── Voucher flow ──────────────────────────────────────────────
        if (Voucher::isVoucherCode($loginInput)) {
            return $this->handleVoucherLogin($request, $loginInput);
        }

        // ── Normal account login flow ─────────────────────────────────
        $request->authenticate();
        $request->session()->regenerate();

        if ($request->filled('mac')) {
            $mac = $request->input('mac');
            $request->session()->put('current_device_mac', $mac);

            try {
                \App\Models\Device::upsertFromLogin(
                    $request->user(),
                    $mac,
                    $request->input('router') ?? null,
                    $request->input('ip') ?? $request->ip(),
                    $request->userAgent() ?? null
                );
            } catch (\Throwable $e) {
                Log::warning('Device upsert failed: ' . $e->getMessage());
            }
        }

        if ($request->filled('router')) {
            $request->session()->put('current_router', $request->input('router'));
        }

        if ($request->filled('link_login')) {
            $user = Auth::user();

            $subscriptionService = new \App\Services\SubscriptionService();
            if (! $subscriptionService->canConnectToHotspot($user)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('dashboard')->with('error', 'Please buy a plan.');
            }

            $linkLogin = $request->input('link_login');
            $linkOrig  = route('dashboard');

            $rad      = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
            $password = $rad ? $rad->value : ($user->radius_password ?? null);

            if (! $password) {
                return redirect()->route('dashboard')->withErrors(['error' => 'Missing router password. Please contact support.']);
            }

            return response()->view('hotspot.redirect_to_router', [
                'username'   => $user->username,
                'password'   => $password,
                'link_login' => $linkLogin,
                'link_orig'  => $linkOrig,
                'mac'        => $request->input('mac'),
                'ip'         => $request->input('ip'),
                'router'     => $request->input('router') ?? null,
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // ─────────────────────────────────────────────────────────────────
    // Voucher redemption
    // ─────────────────────────────────────────────────────────────────

    private function handleVoucherLogin(LoginRequest $request, string $code): RedirectResponse|Response
    {
        $voucher = Voucher::findValid($code);

        if (! $voucher) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'This voucher is invalid, expired, or already used.']);
        }

        $familyHead = $voucher->creator;

        $subscriptionService = new \App\Services\SubscriptionService();
        if (! $subscriptionService->canConnectToHotspot($familyHead)) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'The Family Head\'s plan has expired or run out of data.']);
        }

        $code = strtoupper(trim($code));

        $existingRad = RadCheck::where('username', $code)
            ->where('attribute', 'Cleartext-Password')
            ->first();

        if (! $existingRad) {
            RadCheck::create([
                'username'  => $code,
                'attribute' => 'Cleartext-Password',
                'op'        => ':=',
                'value'     => $code,
            ]);

            RadCheck::create([
                'username'  => $code,
                'attribute' => 'Simultaneous-Use',
                'op'        => ':=',
                'value'     => (string) $voucher->max_uses,
            ]);

            if ($familyHead?->plan) {
                if ($familyHead->plan->bandwidth) {
                    \App\Models\RadReply::create([
                        'username'  => $code,
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op'        => ':=',
                        'value'     => $familyHead->plan->bandwidth,
                    ]);
                }

                $limitMb = $voucher->data_limit_mb ?? ($familyHead->plan->data_limit ?? null);
                if ($limitMb) {
                    RadCheck::create([
                        'username'  => $code,
                        'attribute' => 'Mikrotik-Total-Limit',
                        'op'        => ':=',
                        'value'     => (string) ($limitMb * 1024 * 1024),
                    ]);
                }
            }
        }

        $voucher->consume();

        $linkLogin = $request->input('link_login');
        if ($linkLogin) {
            return response()->view('hotspot.redirect_to_router', [
                'username'   => $code,
                'password'   => $code,
                'link_login' => $linkLogin,
                'link_orig'  => route('home'),
                'mac'        => $request->input('mac'),
                'ip'         => $request->input('ip'),
                'router'     => $request->input('router') ?? null,
            ]);
        }

        return redirect()->route('voucher.success')->with('voucher_code', $code);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}