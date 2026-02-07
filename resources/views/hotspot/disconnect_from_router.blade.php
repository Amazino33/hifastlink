<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Disconnecting from Router...</title>
    <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;display:flex;align-items:center;justify-content:center;height:100vh;background:#f7fafc} .card{background:white;padding:24px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);text-align:center} .hidden{display:none} .btn{margin-top:12px;padding:8px 12px;border-radius:8px;border:0;background:#ef4444;color:white}</style>
</head>
<body>
    <div class="card">
        <p class="mb-4"><strong class="text-lg font-bold">Disconnecting from router...</strong></p>
        <p class="text-sm text-gray-500">You will be redirected back to your dashboard shortly.</p>

        <!-- Fallback button -->
        <div style="margin-top:12px">
            <a id="disconnectBtn" class="btn" href="{{ $logout_url }}">Click here if not redirected</a>
        </div>
    </div>

    <script>
        (function(){
            // Clear the device connection marker from localStorage
            try {
                localStorage.removeItem('hifastlink_device_connected_{{ Auth::id() }}');
            } catch(e) {
                console.error('localStorage not available:', e);
            }
            
            const logoutUrl = @json($logout_url);
            const redirectUrl = @json($redirect_url);

            // Automatically navigate to logout URL
            setTimeout(function(){
                try{
                    // Navigate to router logout
                    window.location.href = logoutUrl;
                    
                    // After a short delay, redirect back to dashboard
                    setTimeout(function(){
                        window.location.href = redirectUrl;
                    }, 1500);
                }catch(e){
                    console.error('Disconnect from router failed:', e);
                    // On error, just redirect to dashboard
                    window.location.href = redirectUrl;
                }
            }, 100);

            // Ensure fallback link uses the correct URL
            var btn = document.getElementById('disconnectBtn');
            if (btn) {
                btn.onclick = function(e) {
                    e.preventDefault();
                    window.location.href = logoutUrl;
                    setTimeout(function(){
                        window.location.href = redirectUrl;
                    }, 1500);
                };
            }
        })();
    </script>
</body>
</html>
