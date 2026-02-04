<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Redirecting to Router...</title>
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;display:flex;align-items:center;justify-content:center;height:100vh;background:#f7fafc} .card{background:white;padding:24px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);text-align:center}</style>
</head>
<body>
    <div class="card">
        <p class="mb-4">Redirecting you to the WiFi hotspot to complete login...</p>
        <p class="text-sm text-gray-500">If you are not redirected automatically, click the button below.</p>
        <form id="hotspot-login-form" action="{{ $link_login }}" method="POST">
            <input type="hidden" name="username" value="{{ $username }}">
            <input type="hidden" name="password" value="{{ $password }}">
            @if(!empty($link_orig))
                <input type="hidden" name="dst" value="{{ $link_orig }}">
            @endif
            @if(!empty($mac))
                <input type="hidden" name="mac" value="{{ $mac }}">
            @endif
            @if(!empty($ip))
                <input type="hidden" name="ip" value="{{ $ip }}">
            @endif
            <button type="submit" style="margin-top:12px;padding:8px 12px;border-radius:8px;border:0;background:#2563eb;color:white">Continue</button>
        </form>
    </div>

    <script>
        (function(){
            try{
                document.getElementById('hotspot-login-form').submit();
            }catch(e){
                console.error('Auto-submit failed:', e);
            }
        })();
    </script>
</body>
</html>