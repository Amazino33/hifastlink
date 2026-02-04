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
        <p class="mb-4">Redirecting you to the WiFi hotspot to complete login...</p>
        <p class="text-sm text-gray-500">If you are not redirected automatically, click the button below.</p>

        <form id="loginForm" method="POST" action="{{ $link_login }}">
            <input type="hidden" name="username" value="{{ $username }}">
            <input type="hidden" name="password" value="{{ $password }}">
            <input type="hidden" name="dst" value="{{ $link_orig }}">
            @if(!empty($mac))
                <input type="hidden" name="mac" value="{{ $mac }}">
            @endif
            @if(!empty($ip))
                <input type="hidden" name="ip" value="{{ $ip }}">
            @endif
            <button id="manualBtn" type="submit" class="btn hidden">Click here if not redirected</button>
        </form>
    </div>

    <script>
        (function(){
            // Auto-submit after 100ms
            setTimeout(function(){
                try{
                    document.getElementById('loginForm').submit();
                }catch(e){
                    console.error('Auto-submit failed:', e);
                }
            }, 100);

            // Show manual button after 3s
            setTimeout(function(){
                var b = document.getElementById('manualBtn');
                if (b) { b.classList.remove('hidden'); }
            }, 3000);
        })();
    </script>
</body>
</html>