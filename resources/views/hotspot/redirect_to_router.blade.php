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
            cursor: pointer
        }
    </style>
</head>

<body>
    <div class="card">
        <p class="mb-4"><strong class="text-lg font-bold">Logging you in...</strong></p>
        <p class="text-sm text-gray-500">You will be redirected back to your dashboard shortly.</p>

        @php
            $dstWithParams = $link_orig;
            $paramsToAdd = [];
            if (!empty($mac))
                $paramsToAdd['mac'] = $mac;
            if (!empty($router))
                $paramsToAdd['router'] = $router;
            if (count($paramsToAdd)) {
                $dstWithParams .= (strpos($dstWithParams, '?') === false ? '?' : '&') . http_build_query($paramsToAdd);
            }
        @endphp

        <form id="mikrotik-login-form" action="{{ preg_replace('#^https://#', 'http://', $link_login) }}" method="POST">
            <input type="hidden" name="username" value="{{ $username }}">
            <input type="hidden" name="password" value="{{ $password }}">
            <input type="hidden" name="dst" value="{{ $dstWithParams }}">

            @if(!empty($mac))
                <input type="hidden" name="mac" value="{{ $mac }}">
            @endif
            @if(!empty($router))
                <input type="hidden" name="router" value="{{ $router }}">
            @endif

            <div style="margin-top:12px">
                <button type="submit" id="connectBtn" class="btn">Click here if not redirected</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            // Get or create unique device ID for this browser
            function getDeviceId() {
                let deviceId = localStorage.getItem('hifastlink_device_id');
                if (!deviceId) {
                    deviceId = 'device_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                    localStorage.setItem('hifastlink_device_id', deviceId);
                }
                return deviceId;
            }

            // Mark THIS specific device as connected
            try {
                const deviceId = getDeviceId();
                const storageKey = 'hifastlink_connected_{{ Auth::id() ?? "guest" }}_' + deviceId;
                localStorage.setItem(storageKey, 'true');
            } catch (e) {
                console.error('localStorage not available:', e);
            }

            // Automatically submit the POST form
            setTimeout(function () {
                try {
                    document.getElementById('mikrotik-login-form').submit();
                } catch (e) {
                    console.error('Form auto-submit failed:', e);
                }
            }, 500);
        })();
    </script>
</body>

</html>