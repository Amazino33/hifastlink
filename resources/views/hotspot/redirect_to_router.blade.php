<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Connecting to WiFi...</title>
    @php
        $dstWithParams = $link_orig;
        $paramsToAdd = [];
        if (!empty($mac)) $paramsToAdd['mac'] = $mac;
        if (!empty($router)) $paramsToAdd['router'] = $router;
        if (count($paramsToAdd)) {
            $dstWithParams .= (strpos($dstWithParams, '?') === false ? '?' : '&') . http_build_query($paramsToAdd);
        }
        $loginParams = ['username' => $username, 'password' => $password, 'dst' => $dstWithParams];
        if (!empty($mac)) $loginParams['mac'] = $mac;
        if (!empty($router)) $loginParams['router'] = $router;
        $finalLoginUrl = $link_login . (strpos($link_login, '?') === false ? '?' : '&') . http_build_query($loginParams);

        // Derive router host for logout URL
        $parsedLogin = parse_url($link_login);
        $host = $parsedLogin['host'] ?? 'login.wifi';
        $scheme = $parsedLogin['scheme'] ?? 'http';
        $logoutUrl = $scheme . '://' . $host . '/logout';
        $isForce = !empty($force_connect);
    @endphp
    {{-- No-JS fallback for captive portal mini-browsers that block JavaScript --}}
    <noscript><meta http-equiv="refresh" content="2;url={{ $finalLoginUrl }}"></noscript>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #0f172a;
            color: #f8fafc;
        }
        .card {
            background: #1e293b;
            padding: 32px 28px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .spinner {
            width: 44px;
            height: 44px;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .btn {
            margin-top: 14px;
            padding: 12px 20px;
            border-radius: 10px;
            border: 0;
            background: #2563eb;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }
        .btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-force {
            background: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
            color: #0f172a;
        }
        .btn-force:hover {
            background: #d97706;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #94a3b8;
            margin-left: 8px;
            box-shadow: none;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="card">
        <div id="loading-spinner" class="spinner"></div>

        <p class="mb-2" style="margin:0 0 8px 0;">
            <strong style="font-size:1.25rem; font-weight:700;">
                {{ $isForce ? 'Force Reconnecting...' : 'Connecting to WiFi...' }}
            </strong>
        </p>
        <p style="font-size:0.875rem; color:#94a3b8; margin:0 0 16px 0;">
            {{ $isForce ? 'Resetting previous session and securing your connection.' : 'You will be connected to the internet shortly.' }}
        </p>

        <div id="connect-action" style="margin-top:12px">
            <a id="connectLink" href="{{ $finalLoginUrl }}" class="btn">
                Click here if not redirected
            </a>
        </div>

        {{-- Loop recovery UI --}}
        <div id="loop-warning" style="display:none; margin-top:20px; padding-top:16px; border-top:1px solid rgba(255,255,255,0.1);">
            <p style="color:#fbbf24; font-size:0.9rem; margin-bottom:14px; line-height:1.4;">
                ⚠️ <strong>Previous session detected.</strong> If your device was recently disconnected, tap below to force-connect.
            </p>
            <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">
                <button type="button" id="forceConnectBtn" class="btn btn-force">
                    ⚡ Force Connect Now
                </button>
                <a id="loginPageLink" href="{{ route('login') }}" class="btn btn-secondary">
                    Login Page
                </a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var MAX_ATTEMPTS = 2;
            var COUNTER_KEY  = 'hfl_redirect_attempts';
            var TS_KEY       = 'hfl_redirect_ts';
            var SESSION_TTL  = 30 * 1000;
            var isForce      = {{ $isForce ? 'true' : 'false' }};
            var logoutUrl    = {{ Js::from($logoutUrl) }};
            var finalLoginUrl= {{ Js::from($finalLoginUrl) }};

            // Force connect resets counters immediately
            if (isForce) {
                sessionStorage.removeItem(COUNTER_KEY);
                sessionStorage.removeItem(TS_KEY);
            }

            var now      = Date.now();
            var attempts = 0;
            var firstTs  = parseInt(sessionStorage.getItem(TS_KEY) || '0', 10);

            if (now - firstTs > SESSION_TTL) {
                sessionStorage.removeItem(COUNTER_KEY);
                sessionStorage.setItem(TS_KEY, now);
            }

            attempts = (parseInt(sessionStorage.getItem(COUNTER_KEY) || '0', 10)) + 1;
            sessionStorage.setItem(COUNTER_KEY, attempts);

            if (attempts > MAX_ATTEMPTS && !isForce) {
                // Loop detected
                sessionStorage.removeItem(COUNTER_KEY);
                sessionStorage.removeItem(TS_KEY);
                document.getElementById('loading-spinner').style.display = 'none';
                document.getElementById('connect-action').style.display = 'none';
                document.getElementById('loop-warning').style.display = 'block';

                var forceBtn = document.getElementById('forceConnectBtn');
                if (forceBtn) {
                    forceBtn.onclick = function() {
                        forceBtn.disabled = true;
                        forceBtn.innerText = 'Resetting session...';
                        // Call server force-disconnect endpoint
                        fetch('{{ route("hotspot.force_connect") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                username: {{ Js::from($username) }},
                                mac: {{ Js::from($mac ?? '') }},
                                router: {{ Js::from($router ?? '') }}
                            })
                        }).finally(function() {
                            // After clearing server session, wipe router host via logout then connect
                            window.location.href = finalLoginUrl;
                        });
                    };
                }
                return;
            }

            // Normal flow
            try {
                var deviceId = localStorage.getItem('hifastlink_device_id');
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
                    window.location.href = finalLoginUrl;
                } catch (e) {
                    console.error('Auto-redirect failed:', e);
                }
            }, isForce ? 700 : 400);
        })();
    </script>
</body>
</html>