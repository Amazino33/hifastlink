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
        <p class="mb-4"><strong>Connecting to Router…</strong></p>
        <p class="text-sm text-gray-500">You will be redirected to your hotspot login. If nothing happens, use the button below.</p>

        <!-- Fallback visible button that performs the same GET-based login -->
        <div style="margin-top:12px">
            <a id="connectBtn" class="btn" href="{{ $link_login }}?username={{ urlencode($username) }}&amp;password={{ urlencode($password) }}&amp;dst={{ urlencode($link_orig) }}">Click here to connect</a>
        </div>
    </div>

    <script>
        (function(){
            // Build a safe URL using JSON-encoded values and encodeURIComponent
            const base = @json($link_login);
            const u = @json($username);
            const p = @json($password);
            const d = @json($link_orig);

            const target = `${base}?username=${encodeURIComponent(u)}&password=${encodeURIComponent(p)}&dst=${encodeURIComponent(d)}`;

            // Automatically navigate using a GET request (bypasses mixed-content form POST issues)
            setTimeout(function(){
                try{
                    window.location.href = target;
                }catch(e){
                    console.error('Redirect to router failed:', e);
                    // If JS redirect fails, ensure the connect button points to the same URL
                    const btn = document.getElementById('connectBtn');
                    if (btn) {
                        btn.href = target;
                    }
                }
            }, 100);

            // Also ensure the fallback link uses the computed URL
            const btn = document.getElementById('connectBtn');
            if (btn) {
                btn.href = target;
            }
        })();
    </script>
</body>
</html>