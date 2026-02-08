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
        // Prepare values
        $locationName = $router->nas_identifier ?: $router->name;
        $radiusSecret = $router->secret;
        $serverIp = env('RADIUS_PUBLIC_IP', env('RADIUS_DB_HOST', config('database.connections.radius.host', '142.93.47.189')));

        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://example.com')), '/');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: preg_replace('#https?://#', '', $appUrl);

        // Escape double quotes inside values for safe injection into .rsc
        $escLocation = str_replace('"', '\\"', $locationName);
        $escServerIp = str_replace('"', '\\"', $serverIp);
        $escSecret = str_replace('"', '\\"', $radiusSecret);
        $escDomain = str_replace('"', '\\"', $domain);

        // Build Robust v4.0 script per specification (no if/else, host-based walled-garden, braces)
        $script = "{\n";
        $script .= "    # Robust v4.0 HiFastLink auto-configuration\n";
        $script .= "    :local LocationName \"{$escLocation}\"\n";
        $script .= "    :local ServerIP \"{$escServerIp}\"\n";
        $script .= "    :local RadiusSecret \"{$escSecret}\"\n";
        $script .= "    :local DomainName \"{$escDomain}\"\n";
        $script .= "    :local HeartbeatURL (\"https://\" . \$DomainName . \"/api/routers/heartbeat?identity=\" . \$LocationName)\n\n";

        // Step 1: Set identity
        $script .= "    # Step 1: Set router identity\n";
        $script .= "    /system identity set name=\$LocationName\n\n";

        // Step 2: Configure RADIUS
        $script .= "    # Step 2: Configure RADIUS\n";
        $script .= "    /radius remove [find]\n";
        $script .= "    /radius add address=\$ServerIP secret=\$RadiusSecret service=hotspot timeout=3s comment=\"HiFastLink RADIUS\"\n\n";

        // Step 3: Configure Hotspot Profile for ALL profiles
        $script .= "    # Step 3: Configure Hotspot Profile (apply to all profiles)\n";
        $script .= "    /ip hotspot profile set [find] use-radius=yes radius-accounting=yes radius-interim-update=1m dns-name=\"login.wifi\" html-directory=hotspot login-by=http-chap,http-pap\n\n";

        // Step 4: Configure User Profile (apply to all user profiles)
        $script .= "    # Step 4: Configure User Profile (shared users)\n";
        $script .= "    /ip hotspot user profile set [find] shared-users=10\n\n";

        // Step 5: Walled Garden entries (host-based)
        $script .= "    # Step 5: Walled Garden - allow paystack and app domain\n";
        $script .= "    /ip hotspot walled-garden add dst-host=\"*.paystack.com\" comment=\"Allow paystack domain\"\n";
        $script .= "    /ip hotspot walled-garden add dst-host=\"*.paystack.co\" comment=\"Allow paystack domain\"\n";
        $script .= "    /ip hotspot walled-garden add dst-host=\$DomainName comment=\"Allow application domain\"\n\n";

        // Step 6: Enable NTP client
        $script .= "    # Step 6: NTP client - enable\n";
        $script .= "    /system ntp client set enabled=yes\n\n";

        // Step 7: Heartbeat scheduler
        $script .= "    # Step 7: Heartbeat scheduler - HTTPS fetch with certificate verification disabled\n";
        $script .= "    /system scheduler add name=\"Heartbeat\" interval=1m on-event=\"/tool fetch url=\"$\"HeartbeatURL\" keep-result=no check-certificate=no\"\n\n";

        $script .= "    :log info \"HiFastLink v4.0 configuration applied for \" . \$LocationName\n";
        $script .= "}\n";

        $filename = 'router-' . ($router->nas_identifier ?: $router->id) . '.rsc';

        return response()->streamDownload(function() use ($script) {
            echo $script;
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
