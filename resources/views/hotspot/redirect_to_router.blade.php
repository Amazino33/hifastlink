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
        <p class="mb-4"><strong>Manual Debug Mode:</strong> Click a button below to connect.</p>
        <p class="text-sm text-gray-500">Auto-submit disabled for debugging. Use the POST button or the GET link to test connectivity with your router.</p>

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
            <!-- Manual POST submit button (visible immediately for debugging) -->
            <button id="manualBtn" type="submit" class="btn">Submit via POST (Manual)</button>
        </form>

        <!-- Force Connect (GET) link for debugging -->
        <div style="margin-top:10px">
            <a id="forceGet" href="{{ $link_login }}?username={{ urlencode($username) }}&amp;password={{ urlencode($password) }}&amp;dst={{ urlencode($link_orig) }}" style="color:#b91c1c;font-weight:bold;text-decoration:underline;">Force Connect (GET)</a>
        </div>
    </div>

    <script>
        (function(){
            // Auto-submit disabled for debugging - developer will click the POST button manually.
            // setTimeout(function(){
            //     try{
            //         document.getElementById('loginForm').submit();
            //     }catch(e){
            //         console.error('Auto-submit failed:', e);
            //     }
            // }, 100);

            // Manual button is visible immediately; no delayed reveal needed.
        })();
    </script>
</body>
</html>