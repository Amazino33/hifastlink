<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Connecting...</title>
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;display:flex;align-items:center;justify-content:center;height:100vh;background:#f7fafc}.card{background:white;padding:24px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);text-align:center}.btn{margin-top:12px;padding:8px 16px;border-radius:8px;border:0;background:#2563eb;color:white;cursor:pointer;font-size:14px}</style>
</head>
<body>
    <div class="card">
        <p><strong>Logging you in...</strong></p>
        <p style="color:#6b7280;font-size:14px">You will be redirected to your dashboard shortly.</p>
        <button class="btn" onclick="doLogin()">Tap here if not redirected</button>
    </div>

    @php
        $loginBase = 'http://' . preg_replace('#^https?://#', '', $link_login);
        $params = array_filter([
            'username' => $username,
            'password' => $password,
            'dst'      => $link_orig ?? null,
            'mac'      => $mac ?? null,
            'router'   => $router ?? null,
        ]);
        $loginHref = $loginBase . '?' . http_build_query($params);
    @endphp

    <script>
        var loginUrl = "{{ $loginHref }}";

        function doLogin() {
            window.location.href = loginUrl;
        }

        // Auto-redirect after short delay
        setTimeout(doLogin, 500);
    </script>
</body>
</html>