<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Redirecting to Router...</title>
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;display:flex;align-items:center;justify-content:center;height:100vh;background:#f7fafc} .card{background:white;padding:24px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);text-align:center} .hidden{display:none} .btn{margin-top:12px;padding:8px 12px;border-radius:8px;border:0;background:#2563eb;color:white}</style>
</head>
<body>
    <div class="card">
        <p class="mb-4"><strong class="text-lg font-bold">Logging you in...</strong></p>
        <p class="text-sm text-gray-500">You will be redirected back to your dashboard shortly.</p>

        <!-- Subtle fallback button that performs the same GET-based login -->
        @php
            $dstWithParams = $link_orig;
            $paramsToAdd = [];
            if (!empty($mac)) $paramsToAdd['mac'] = $mac;
            if (!empty($router)) $paramsToAdd['router'] = $router;
            if (count($paramsToAdd)) {
                $dstWithParams .= (strpos($dstWithParams, '?') === false ? '?' : '&') . http_build_query($paramsToAdd);
            }
        @endphp
        <div style="margin-top:12px">
            <a id="connectBtn" class="btn" href="{{ $link_login }}?username={{ urlencode($username) }}&amp;password={{ urlencode($password) }}&amp;dst={{ urlencode($dstWithParams) }}">Click here if not redirected</a>
        </div>
    </div>

    <script>
        (function(){
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
                const storageKey = 'hifastlink_connected_{{ Auth::id() }}_' + deviceId;
                localStorage.setItem(storageKey, 'true');
            } catch(e) {
                console.error('localStorage not available:', e);
            }
            
            const base = @json($link_login);
            const u = @json($username);
            const p = @json($password);
            const d = @json($link_orig);
            const m = @json($mac ?? '');
            const r = @json($router ?? '');

            // Append mac/router to dst so dashboard receives them, and also include as top-level params
            let dstWithParams = d || '';
            const extra = [];
            if (m) extra.push('mac=' + encodeURIComponent(m));
            if (r) extra.push('router=' + encodeURIComponent(r));
            if (extra.length) {
                dstWithParams += (dstWithParams.indexOf('?') === -1 ? '?' : '&') + extra.join('&');
            }

            const target = `${base}?username=${encodeURIComponent(u)}&password=${encodeURIComponent(p)}&dst=${encodeURIComponent(dstWithParams)}${m ? '&mac=' + encodeURIComponent(m) : ''}${r ? '&router=' + encodeURIComponent(r) : ''}`;

            // Automatically navigate using a GET request; this bypasses mixed-content POST restrictions
            setTimeout(function(){
                try{
                    window.location.href = target;
                }catch(e){
                    console.error('Redirect to router failed:', e);
                    var btn = document.getElementById('connectBtn');
                    if (btn) btn.href = target;
                }
            }, 100);

            // Ensure fallback link uses the same URL
            var btn = document.getElementById('connectBtn');
            if (btn) btn.href = target;
        })();
    </script>
</body>
</html>