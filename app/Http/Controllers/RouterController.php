<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Router;

class RouterController extends Controller
{

    /**
     * Return router login credentials for the authenticated user.
     */
    public function credentials(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Determine if user has an active subscription.
        // Prefer direct subscription table query if Subscription model exists; otherwise fall back to user plan fields.
        $validSubscription = null;

        if (class_exists(\App\Models\Subscription::class)) {
            $validSubscription = \App\Models\Subscription::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->where('expires_at', '>', now())
                ->where(function ($q) {
                    $q->where('data_remaining', '>', 0)
                      ->orWhereNull('data_limit');
                })
                ->orderBy('expires_at', 'desc')
                ->first();
        } else {
            // fallback: check user's plan_expiry and remaining bytes
            $hasExpiry = $user->plan_expiry && $user->plan_expiry->isFuture();
            $dataRemaining = is_null($user->data_limit) ? null : max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));

            if ($hasExpiry && (is_null($user->data_limit) || $dataRemaining > 0)) {
                $validSubscription = (object) [
                    'plan_id' => $user->plan_id,
                    'expires_at' => $user->plan_expiry,
                ];
            }
        }

        if (! $validSubscription) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        // Self-repair: if a valid subscription exists but user.plan_id is missing, repair it immediately
        if (isset($validSubscription->plan_id) && empty($user->plan_id) && $validSubscription->plan_id) {
            try {
                $user->plan_id = $validSubscription->plan_id;
                $user->save();
                \Illuminate\Support\Facades\Log::info('Repaired missing plan_id for user '.$user->id.' using subscription.'.($validSubscription->id ?? ''));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to repair user.plan_id for user '.$user->id.': '.$e->getMessage());
            }
        }

        // Check for required credentials
        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router credentials missing for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        // Determine login URL (use services.mikrotik.gateway or env MIKROTIK_LOGIN_URL, fallback to sensible default)
        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL') ?? 'http://192.168.88.1/login';

        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (!preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        return response()->json([
            'username' => $user->username,
            'password' => $user->radius_password,
            'login_url' => $loginUrl,
            'dashboard_url' => route('dashboard'),
        ]);
    }

    /**
     * Attempt a server-side login via the RADIUS bridge. Intended for captive portal flows.
     * Expects optional 'mac', 'ip', 'link-login' in the request.
     */
    public function bridgeLogin(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!method_exists($user, 'isSubscriptionActive') || !$user->isSubscriptionActive()) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router bridge login failed - missing credentials for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        $mac = $request->input('mac');
        $ip = $request->input('ip');
        $linkLogin = $request->input('link-login') ?? $request->input('link-login-only') ?? $request->input('link-orig');

        $bridgeUrl = rtrim(config('services.radius.bridge_url') ?? env('RADIUS_BRIDGE_URL', ''), '/');
        $secret = config('services.radius.secret_key') ?? env('RADIUS_SECRET_KEY', null);

        // If we have a bridge, attempt server-side POST to router via bridge
        if ($bridgeUrl && $secret) {
            try {
                $resp = \Illuminate\Support\Facades\Http::post($bridgeUrl . '/login', array_filter([
                    'username' => $user->username,
                    'password' => $user->radius_password,
                    'secret' => $secret,
                    'mac' => $mac,
                    'ip' => $ip,
                    'link' => $linkLogin,
                ]));

                if ($resp->successful()) {
                    Log::info('Router bridge login successful for user id: ' . $user->id);
                    return response()->json(['success' => true, 'redirect' => $linkLogin ?? null]);
                }

                Log::warning('Router bridge login returned non-200 for user id ' . $user->id, ['body' => $resp->body()]);
            } catch (\Exception $e) {
                Log::error('Router bridge login error for user id ' . $user->id . ': ' . $e->getMessage());
            }

            // If bridge failed, fallthrough to client-side submission fallback
        }

        // Fallback: return login info for client to submit directly (includes dashboard URL so router can return the user)
        $fallbackGateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL') ?? 'http://192.168.88.1/login';
        $fallbackLoginUrl = (strpos($fallbackGateway, '://') === false ? 'http://' . $fallbackGateway : $fallbackGateway);
        if (!preg_match('#/login#', $fallbackLoginUrl)) {
            $fallbackLoginUrl = rtrim($fallbackLoginUrl, '/') . '/login';
        }

        return response()->json([
            'success' => false,
            'message' => 'Bridge unavailable. You will be redirected to router to complete login.',
            'username' => $user->username,
            'password' => $user->radius_password,
            'login_url' => $fallbackLoginUrl,
            'dashboard_url' => route('dashboard'),
            'redirect' => $linkLogin ?? null,
        ]);
    }

    /**
     * Download generated MikroTik configuration (.rsc) for a router.
     */
    public function downloadConfig(Router $router)
    {
        $locationName = $router->nas_identifier ?: $router->name;
        $radiusSecret = $router->secret;

        $serverIp = env('RADIUS_PUBLIC_IP', env('RADIUS_DB_HOST', config('database.connections.radius.host', '142.93.47.189')));

        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://example.com')), '/');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;
        $token = env('ROUTER_HEARTBEAT_TOKEN');

        $heartbeatUrl = $appUrl . '/api/routers/heartbeat?identity=' . rawurlencode($locationName);
        if ($token) {
            $heartbeatUrl .= '&token=' . rawurlencode($token);
        }

        $script = "# HiFastLink generated router configuration for {$router->name}\n";
        $script .= ":log info \"Applying HiFastLink configuration for {$router->name}\"\n\n";

        $script .= "# RADIUS server\n";
        $script .= "/radius remove [find]\n";
        $script .= "/radius add address={$serverIp} secret={$radiusSecret} service=hotspot timeout=3s comment=\"HiFastLink RADIUS\"\n\n";

        $script .= "# Hotspot profile (idempotent)\n";
        $script .= ":if ([/ip hotspot profile find name=hsprof1] = \"\") do={\n";
        $script .= "    /ip hotspot profile add name=hsprof1 hotspot-address=192.168.88.1 dns-name=\"login.wifi\" login-by=http-chap,http-pap use-radius=yes radius-accounting=yes radius-interim-update=1m nas-port-type=wireless-802.11 shared-users=10 comment=\"HiFastLink Hotspot Profile\"\n";
        $script .= "} else={\n";
        $script .= "    /ip hotspot profile set [find name=hsprof1] use-radius=yes radius-accounting=yes radius-interim-update=1m shared-users=10\n";
        $script .= "}\n\n";

        $script .= ":log info \"Allowing heartbeat to pass via walled-garden for {$domain} and {$serverIp}\"\n";
        $script .= "/ip hotspot walled-garden ip add dst-host={$domain} comment=\"Allow heartbeat to app\"\n";
        $script .= "/ip hotspot walled-garden ip add dst-host={$serverIp} comment=\"Allow heartbeat to RADIUS\"\n\n";

        $script .= "# Heartbeat scheduler (fetches over HTTPS) - do NOT verify certificate on router\n";
        $script .= "/system scheduler add name=\"Heartbeat\" interval=1m on-event=\"/tool fetch url=\\\"{$heartbeatUrl}\\\" keep-result=no check-certificate=no\"\n\n";

        $script .= ":log info \"HiFastLink configuration complete for {$router->name}\"\n";

        $filename = 'router-' . ($router->nas_identifier ?: $router->id) . '.rsc';

        return response()->streamDownload(function() use ($script) {
            echo $script;
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
