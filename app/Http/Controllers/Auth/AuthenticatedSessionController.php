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
    public function create(): View|Response|RedirectResponse
    {
        $linkLogin = request()->get('link-login')
                  ?? request()->get('link-login-only')
                  ?? request()->get('link_login')
                  ?? request()->get('link-orig');

        $mac = request()->get('mac');

        // Check if we should skip auto-login due to recent voucher failure
        if (session()->get('skip_auto_login')) {
            session()->forget('skip_auto_login');
            return view('auth.captive-portal');
        }

        // ── Layer 1: Regular user MAC auto-reconnect ──────────────────────
        if ($linkLogin && $mac) {
            $device = \App\Models\Device::where('mac', $mac)
                ->whereNotNull('user_id')
                ->with('user')
                ->first();

            if ($device?->user) {
                $user = $device->user;
                $subscriptionService = new \App\Services\SubscriptionService;

                if ($subscriptionService->canConnectToHotspot($user)) {
                    $rad = RadCheck::where('username', $user->username)
                        ->where('attribute', 'Cleartext-Password')
                        ->first();
                    $password = $rad?->value ?? $user->radius_password;

                    if ($password) {
                        Auth::login($user, remember: true);
                        request()->session()->regenerate();
                        request()->session()->save(); // force-persist before the JS redirect chain leaves the app

                        try {
                            \App\Models\Device::upsertFromLogin(
                                $user,
                                $mac,
                                request()->get('router'),
                                request()->get('ip') ?? request()->ip(),
                                request()->userAgent()
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Device upsert failed during MAC auto-reconnect: '.$e->getMessage());
                        }

                        session(['bridge_completed' => true]);

                        return response()->view('hotspot.redirect_to_router', [
                            'username' => $user->username,
                            'password' => $password,
                            'link_login' => $linkLogin,
                            'link_orig' => route('captive.connected'),
                            'mac' => $mac,
                            'ip' => request()->get('ip'),
                            'router' => request()->get('router'),
                        ]);
                    }
                }
            }
        }

        // ── Layer 1b: Voucher MAC auto-reconnect ──────────────────────────
        if ($linkLogin && $mac) {
            $voucherDevice = \App\Models\Device::where('mac', $mac)
                ->whereNull('user_id')
                ->whereNotNull('meta')
                ->first();

            $storedCode = $voucherDevice?->meta['voucher_code'] ?? null;

            if ($storedCode) {
                $radExists = RadCheck::where('username', $storedCode)
                    ->where('attribute', 'Cleartext-Password')
                    ->exists();

                if (! $radExists) {
                    $storedVoucher = \App\Models\Voucher::where('code', $storedCode)->first();
                    $storedCreator = $storedVoucher?->creator;

                    // Admin-created vouchers (no creator) check own expiry.
                    // Creator-based vouchers live and die with the creator's plan.
                    $canRestore = false;
                    if ($storedVoucher) {
                        if ($storedCreator) {
                            $canRestore = (new \App\Services\SubscriptionService)->canConnectToHotspot($storedCreator);
                        } else {
                            $canRestore = ! $storedVoucher->expires_at || $storedVoucher->expires_at->isFuture();
                        }
                    }

                    if ($canRestore) {
                        // Restore core RADIUS credentials
                        RadCheck::updateOrCreate(
                            ['username' => $storedCode, 'attribute' => 'Cleartext-Password'],
                            ['op' => ':=', 'value' => $storedCode]
                        );
                        RadCheck::updateOrCreate(
                            ['username' => $storedCode, 'attribute' => 'Simultaneous-Use'],
                            ['op' => ':=', 'value' => (string) $storedVoucher->max_uses]
                        );

                        // Restore Expiration: creator-based → creator's plan_expiry
                        $restoreExpiry = $storedCreator?->plan_expiry ?? $storedVoucher->expires_at;
                        if ($restoreExpiry) {
                            RadCheck::updateOrCreate(
                                ['username' => $storedCode, 'attribute' => 'Expiration'],
                                ['op' => ':=', 'value' => $restoreExpiry->format('d M Y H:i')]
                            );
                        }

                        // Restore speed limits
                        $fallbackPlan = $storedVoucher->plan ?? $storedVoucher->creator?->plan;
                        $uploadKbps   = $storedVoucher->speed_limit_upload   ?? $fallbackPlan?->speed_limit_upload;
                        $downloadKbps = $storedVoucher->speed_limit_download ?? $fallbackPlan?->speed_limit_download;
                        if ($uploadKbps || $downloadKbps) {
                            \App\Models\RadReply::updateOrCreate(
                                ['username' => $storedCode, 'attribute' => 'Mikrotik-Rate-Limit'],
                                ['op' => ':=', 'value' => ($uploadKbps ?? 0) . 'k/' . ($downloadKbps ?? 0) . 'k']
                            );
                        }

                        // Restore data cap
                        if (! $storedVoucher->is_unlimited) {
                            $refPlan = $storedVoucher->plan ?? $storedVoucher->creator?->plan;
                            $planLimitMb = null;
                            if ($refPlan && $refPlan->limit_unit !== 'Unlimited' && $refPlan->data_limit) {
                                $planLimitMb = $refPlan->limit_unit === 'GB'
                                    ? (int) ($refPlan->data_limit * 1024)
                                    : (int) $refPlan->data_limit;
                            }
                            $limitMb = $storedVoucher->data_limit_mb ?? $planLimitMb;
                            if ($limitMb) {
                                \App\Models\RadReply::updateOrCreate(
                                    ['username' => $storedCode, 'attribute' => 'Mikrotik-Total-Limit'],
                                    ['op' => ':=', 'value' => (string) ($limitMb * 1048576)]
                                );
                            }
                        }

                        $radExists = true;
                    }
                }

                if ($radExists) {
                    $voucherDevice->update([
                        'last_seen' => now(),
                        'is_connected' => true,
                        'ip' => request()->get('ip') ?? request()->ip(),
                    ]);

                    session(['bridge_completed' => true]);

                    return response()->view('hotspot.redirect_to_router', [
                        'username' => $storedCode,
                        'password' => $storedCode,
                        'link_login' => $linkLogin,
                        'link_orig' => route('captive.connected'),
                        'mac' => $mac,
                        'ip' => request()->get('ip'),
                        'router' => request()->get('router'),
                    ]);
                }
            }
        }

        // ── Layer 2: Active Laravel session ───────────────────────────────
        if (auth()->check()) {
            $user = auth()->user();

            if ($linkLogin) {
                $subscriptionService = new \App\Services\SubscriptionService;

                if ($subscriptionService->canConnectToHotspot($user)) {
                    $rad = RadCheck::where('username', $user->username)
                        ->where('attribute', 'Cleartext-Password')
                        ->first();
                    $password = $rad?->value ?? $user->radius_password;

                    if ($mac) {
                        try {
                            \App\Models\Device::upsertFromLogin(
                                $user,
                                $mac,
                                request()->get('router'),
                                request()->get('ip') ?? request()->ip(),
                                request()->userAgent()
                            );
                        } catch (\Throwable $e) {
                            Log::warning('Device upsert failed during session auto-reconnect: '.$e->getMessage());
                        }
                    }

                    if ($password) {
                        session(['bridge_completed' => true]);

                        return response()->view('hotspot.redirect_to_router', [
                            'username' => $user->username,
                            'password' => $password,
                            'link_login' => $linkLogin,
                            'link_orig' => route('captive.connected'),
                            'mac' => $mac,
                            'ip' => request()->get('ip'),
                            'router' => request()->get('router'),
                        ]);
                    }
                }
            }

            // Authenticated but no captive portal params — go to dashboard
            return redirect()->route('dashboard');
        }

        // ── Layer 3: Unknown — show appropriate form based on context ───
        // MikroTik always passes link-login; regular browser visits get the standard login page.
        if ($linkLogin) {
            return view('auth.captive-portal');
        }

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
        $request->session()->forget('skip_auto_login');

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
                Log::warning('Device upsert failed: '.$e->getMessage());
            }
        }

        if ($request->filled('router')) {
            $request->session()->put('current_router', $request->input('router'));
        }

        if ($request->filled('link_login')) {
            $user = Auth::user();

            $subscriptionService = new \App\Services\SubscriptionService;
            if (! $subscriptionService->canConnectToHotspot($user)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('dashboard')->with('error', 'Please buy a plan.');
            }

            $linkLogin = $request->input('link_login');
            $linkOrig = route('dashboard');

            $rad = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
            $password = $rad ? $rad->value : ($user->radius_password ?? null);

            if (! $password) {
                return redirect()->route('dashboard')->withErrors(['error' => 'Missing router password. Please contact support.']);
            }

            session(['bridge_completed' => true]);

            return response()->view('hotspot.redirect_to_router', [
                'username' => $user->username,
                'password' => $password,
                'link_login' => $linkLogin,
                'link_orig' => $linkOrig,
                'mac' => $request->input('mac'),
                'ip' => $request->input('ip'),
                'router' => $request->input('router') ?? null,
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // ─────────────────────────────────────────────────────────────────
    // Voucher redemption
    // ─────────────────────────────────────────────────────────────────

    private function handleVoucherLogin(LoginRequest $request, string $code): RedirectResponse|Response
    {
        $request->session()->forget('skip_auto_login');

        $normalCode = strtoupper(trim($code));

        // Check if this device's MAC is already registered for this voucher.
        // If so, we allow reconnect even if the voucher's used_count has reached max_uses,
        // and we skip consume() so reconnecting devices don't exhaust the slot limit.
        $mac = $request->filled('mac') ? strtoupper($request->input('mac')) : null;

        // Primary check: MAC + voucher pairing in devices table
        $macKnown = $mac && \App\Models\Device::where('mac', $mac)
            ->whereNull('user_id')
            ->where('meta->voucher_code', $normalCode)
            ->exists();

        // Fallback: if no MAC was sent, treat as a reconnect when RADIUS credentials
        // already exist — the Simultaneous-Use RADIUS attribute enforces the actual
        // device cap, so we don't need used_count to gate this.
        $radExists = ! $mac && RadCheck::where('username', $normalCode)
            ->where('attribute', 'Cleartext-Password')
            ->exists();

        $alreadyRegistered = $macKnown || $radExists;

        if ($alreadyRegistered) {
            // Returning device: skip used_count gate but still enforce expiry
            $voucher = Voucher::where('code', $normalCode)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->first();
        } else {
            // New device: full validity check (used_count < max_uses + not expired)
            $voucher = Voucher::findValid($code);
        }

        if (! $voucher) {
            $request->session()->put('skip_auto_login', true);
            return back()
                ->withInput()
                ->withErrors(['login' => 'This voucher is invalid, expired, or already used.']);
        }

        $familyHead = $voucher->creator;

        // Admin-created vouchers have no creator — they're standalone and always valid
        if ($familyHead) {
            $subscriptionService = new \App\Services\SubscriptionService;
            if (! $subscriptionService->canConnectToHotspot($familyHead)) {
                $request->session()->put('skip_auto_login', true);
                return back()
                    ->withInput()
                    ->withErrors(['login' => 'The voucher owner\'s plan has expired or run out of data.']);
            }
        }

        $code = $normalCode;

        if (! $alreadyRegistered) {
            // Consume first so expires_at is calculated from redemption time (not creation time)
            // and is available for the RADIUS Expiration attribute below.
            $voucher->consume();
        }

        // Always upsert so concurrent redemptions never produce duplicate rows
        RadCheck::updateOrCreate(
            ['username' => $code, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $code]
        );

        RadCheck::updateOrCreate(
            ['username' => $code, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => (string) $voucher->max_uses]
        );

        // Expiration: creator-based → creator's plan_expiry; admin-created → voucher's own expires_at
        $expirationDate = $familyHead?->plan_expiry ?? $voucher->expires_at;
        if ($expirationDate) {
            RadCheck::updateOrCreate(
                ['username' => $code, 'attribute' => 'Expiration'],
                ['op' => ':=', 'value' => $expirationDate->format('d M Y H:i')]
            );
        }

        // Speed limits: prefer voucher's own settings, fall back to linked plan or creator's plan
        $fallbackPlan = $voucher->plan ?? $familyHead?->plan;
        $uploadKbps   = $voucher->speed_limit_upload   ?? $fallbackPlan?->speed_limit_upload;
        $downloadKbps = $voucher->speed_limit_download ?? $fallbackPlan?->speed_limit_download;
        if ($uploadKbps || $downloadKbps) {
            \App\Models\RadReply::updateOrCreate(
                ['username' => $code, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => ($uploadKbps ?? 0) . 'k/' . ($downloadKbps ?? 0) . 'k']
            );
        }

        // Data cap: honour is_unlimited flag; prefer voucher's own limit over plan's.
        // Mikrotik-Total-Limit goes into radREPLY (sent to MikroTik as a per-session cap),
        // NOT radcheck (which FreeRADIUS sqlcounter would treat as a cumulative shared cap
        // across all devices using this username, blocking device 4+ once the total is hit).
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
                    ['username' => $code, 'attribute' => 'Mikrotik-Total-Limit'],
                    ['op' => ':=', 'value' => (string) ($limitMb * 1048576)]
                );
            } else {
                \App\Models\RadReply::where('username', $code)->where('attribute', 'Mikrotik-Total-Limit')->delete();
            }
            // Remove any stale radcheck entry from before this fix
            RadCheck::where('username', $code)->where('attribute', 'Mikrotik-Total-Limit')->delete();
        } else {
            // Unlimited — remove any stale cap from either table
            RadCheck::where('username', $code)->where('attribute', 'Mikrotik-Total-Limit')->delete();
            \App\Models\RadReply::where('username', $code)->where('attribute', 'Mikrotik-Total-Limit')->delete();
        }

        // Store MAC so this device can auto-reconnect after router reboots
        if ($request->filled('mac')) {
            \App\Models\Device::updateOrCreate(
                ['mac' => strtoupper($request->input('mac'))],
                [
                    'user_id' => null,
                    'router_id' => $voucher->router_id,
                    'ip' => $request->input('ip') ?? $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'first_seen' => now(),
                    'last_seen' => now(),
                    'is_connected' => true,
                    'meta' => ['voucher_code' => $code],
                ]
            );
        }

        $request->session()->forget('skip_auto_login');
        $request->session()->save();

        // If the request came through the MikroTik captive portal, push the credentials
        // straight to the router so the user gets online immediately — same flow as regular login.
        $linkLogin = $request->input('link_login')
            ?? $request->input('link-login')
            ?? $request->input('link-login-only');

        if ($linkLogin) {
            $linkOrig = route('voucher.success', ['code' => $code]);

            session(['bridge_completed' => true]);

            return response()->view('hotspot.redirect_to_router', [
                'username'   => $code,
                'password'   => $code,
                'link_login' => $linkLogin,
                'link_orig'  => $linkOrig,
                'mac'        => $request->input('mac'),
                'ip'         => $request->input('ip'),
                'router'     => $request->input('router'),
            ]);
        }

        // No captive portal link — user opened the login page directly in a browser.
        // Just show the success page; they can connect manually from the dashboard.
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
