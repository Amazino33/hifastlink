<?php

// app/Http/Controllers/Auth/AuthenticatedSessionController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\RadCheck;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
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
                \Log::warning('Device upsert failed: '.$e->getMessage());
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
        $voucher = Voucher::findValid($code);

        if (! $voucher) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'This voucher is invalid, expired, or already used.']);
        }

        $familyHead = $voucher->creator;

        // Check if the Family Head's plan is still active!
        $subscriptionService = new \App\Services\SubscriptionService();
        if (!$subscriptionService->canConnectToHotspot($familyHead)) {
            return back()
                ->withInput()
                ->withErrors(['login' => 'The Family Head\'s plan has expired or run out of data.']);
        }
        
        $code = strtoupper(trim($code));

        // 1. Check/Create the Password entry
        $existingRad = RadCheck::where('username', $code)
            ->where('attribute', 'Cleartext-Password')
            ->first();

        if (! $existingRad) {
            // Basic Auth
            RadCheck::create([
                'username' => $code,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $code,
            ]);

            // Device Limit
            RadCheck::create([
                'username' => $code,
                'attribute' => 'Simultaneous-Use',
                'op' => ':=',
                'value' => (string) $voucher->max_uses,
            ]);

            // --- TIE TO FAMILY HEAD PLAN LIMITS ---
            if ($familyHead && $familyHead->plan) {
                // A. Speed Limit (Rate-Limit)
                // Assuming your plan has a 'bandwidth' column like '2M/2M'
                if ($familyHead->plan->bandwidth) {
                    \App\Models\RadReply::create([
                        'username' => $code,
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op' => ':=',
                        'value' => $familyHead->plan->bandwidth,
                    ]);
                }

                // B. Data Limit (Total-Limit)
                // Use the voucher's data limit if it has one, otherwise inherit family head's
                $limitMb = $voucher->data_limit_mb ?? ($familyHead->plan->data_limit ?? null);
                if ($limitMb) {
                    $bytes = $limitMb * 1024 * 1024;
                    RadCheck::create([
                        'username' => $code,
                        'attribute' => 'Mikrotik-Total-Limit',
                        'op' => ':=',
                        'value' => (string) $bytes,
                    ]);
                }
            }
        }

        // Mark the voucher as consumed
        $voucher->consume();

        // Captive portal logic...
        $linkLogin = $request->input('link_login');
        if ($linkLogin) {
            return response()->view('hotspot.redirect_to_router', [
                'username' => $code,
                'password' => $code,
                'link_login' => $linkLogin,
                'link_orig' => route('home'),
                'mac' => $request->input('mac'),
                'ip' => $request->input('ip'),
                'router' => $request->input('router') ?? null,
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
