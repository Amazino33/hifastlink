<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Redirecting to Router...</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #f7fafc
        }
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
            text-align: center
        }
        .btn {
            margin-top: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 0;
            background: #2563eb;
            color: white;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary {
            background: #6b7280;
        }
    </style>
</head>
<body>
    <div @class(['card'])>
        <p @class(['mb-4'])><strong @class(['text-lg', 'font-bold'])>Logging you in...</strong></p>
        <p @class(['text-sm', 'text-gray-500'])>You will be redirected back to your dashboard shortly.</p>

        @php
            $dstWithParams = $link_orig;
            $paramsToAdd = [];
            if (!empty($mac)) $paramsToAdd['mac'] = $mac;
            if (!empty($router)) $paramsToAdd['router'] = $router;
            if (count($paramsToAdd)) {
                $dstWithParams .= (strpos($dstWithParams, '?') === false ? '?' : '&') . http_build_query($paramsToAdd);
            }

            $loginParams = [
                'username' => $username,
                'password' => $password,
                'dst'      => $dstWithParams
            ];
            if (!empty($mac)) $loginParams['mac'] = $mac;
            if (!empty($router)) $loginParams['router'] = $router;

            $finalLoginUrl = $link_login . (strpos($link_login, '?') === false ? '?' : '&') . http_build_query($loginParams);
        @endphp

        <div style="margin-top:12px">
            <a id="connectLink" href="{{ $finalLoginUrl }}" @class(['btn'])>Click here if not redirected</a>
        </div>

        {{-- Shown only when the loop is detected (injected by JS below) --}}
        <div id="loop-warning" style="display:none; margin-top:16px;">
            <p style="color:#dc2626; font-size:0.875rem; margin-bottom:8px;">
                ⚠️ It looks like your credentials didn't work. Please log in again with the correct voucher.
            </p>
            <a id="loginPageLink" href="{{ route('login') }}" @class(['btn', 'btn-secondary'])>
                Go to login page
            </a>
        </div>
    </div>

    <script>
        (function () {
            var MAX_ATTEMPTS = 3;
            var COUNTER_KEY  = 'hfl_redirect_attempts';
            var TS_KEY       = 'hfl_redirect_ts';
            var SESSION_TTL  = 30 * 1000; // 30 s — treat anything older as a fresh attempt

            // ── 1. Loop detection ──────────────────────────────────────────
            var now      = Date.now();
            var attempts = 0;
            var firstTs  = parseInt(sessionStorage.getItem(TS_KEY) || '0', 10);

            // Reset the counter if the last hit was too long ago (user came back later)
            if (now - firstTs > SESSION_TTL) {
                sessionStorage.removeItem(COUNTER_KEY);
                sessionStorage.setItem(TS_KEY, now);
            }

            attempts = (parseInt(sessionStorage.getItem(COUNTER_KEY) || '0', 10)) + 1;
            sessionStorage.setItem(COUNTER_KEY, attempts);

            if (attempts >= MAX_ATTEMPTS) {
                // Loop detected — stop, clear counter, show the warning UI
                sessionStorage.removeItem(COUNTER_KEY);
                sessionStorage.removeItem(TS_KEY);
                document.getElementById('loop-warning').style.display = 'block';
                return; // <-- bail out entirely; no auto-redirect
            }

            // ── 2. Normal flow (first or second pass) ─────────────────────
            try {
                var deviceId   = localStorage.getItem('hifastlink_device_id');
                if (!deviceId) {
                    deviceId = 'device_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                    localStorage.setItem('hifastlink_device_id', deviceId);
                }
                var storageKey = 'hifastlink_connected_{{ Auth::id() ?? "guest" }}_' + deviceId;
                localStorage.setItem(storageKey, 'true');
            } catch (e) {
                console.error('localStorage not available:', e);
            }

            setTimeout(function () {
                try {
                    window.location.href = document.getElementById('connectLink').href;
                } catch (e) {
                    console.error('Auto-redirect failed:', e);
                }
            }, 500);
        })();
    </script>
</body>
</html>