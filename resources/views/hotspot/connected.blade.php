<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#007AFE">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="HiFastLink">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/pwa-icon.svg">
    <title>Connected!</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f0fdf4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 40px 28px 32px;
            text-align: center;
            max-width: 360px;
            width: 100%;
        }
        .icon {
            width: 72px; height: 72px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .icon svg { width: 40px; height: 40px; stroke: #16a34a; }
        h1 { font-size: 22px; font-weight: 700; color: #111; margin-bottom: 8px; }
        p  { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 24px; }
        .btn {
            display: block;
            background: #007AFE; color: #fff;
            text-decoration: none;
            font-size: 15px; font-weight: 600;
            padding: 13px 28px;
            border-radius: 12px;
            border: none; cursor: pointer;
            width: 100%;
            transition: opacity .15s;
        }
        .btn:active { opacity: .82; }

        /* Install hint */
        .install-card {
            margin-top: 20px;
            background: #f0f7ff;
            border-radius: 14px;
            padding: 16px;
            text-align: left;
        }
        .install-card-title {
            font-size: 13px; font-weight: 700; color: #007AFE;
            margin-bottom: 6px;
        }
        .install-card-body {
            font-size: 12px; color: #374151; line-height: 1.6;
            margin-bottom: 0;
        }
        #install-btn { display: none; margin-top: 10px; font-size: 13px; padding: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
        </div>

        <h1>You're Connected!</h1>
        <p>You now have internet access on HiFastLink.<br>You can close this and start browsing.</p>

        <a href="https://hifastlink.com/dashboard" class="btn">Go to Dashboard</a>

        {{-- Install prompt — shown only if not already a standalone PWA --}}
        <div class="install-card" id="install-card" style="display:none">
            <p class="install-card-title">📲 Connect faster next time</p>
            <p class="install-card-body" id="install-body">
                Install the HiFastLink app and connect with one tap — no typing required.
            </p>
            <button class="btn" id="install-btn">Install App</button>
        </div>
    </div>

    <script>
        var deferredInstall = null;

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredInstall = e;
            showInstall();
        });

        function showInstall() {
            var standalone = window.matchMedia('(display-mode: standalone)').matches
                          || window.navigator.standalone === true;
            if (standalone) return;

            var card = document.getElementById('install-card');
            var body = document.getElementById('install-body');
            var btn  = document.getElementById('install-btn');
            var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);

            if (isIos) {
                card.style.display = 'block';
                body.textContent = 'In Safari, tap the Share button then "Add to Home Screen" to install.';
            } else if (deferredInstall) {
                card.style.display = 'block';
                btn.style.display = 'block';
                btn.addEventListener('click', function () {
                    deferredInstall.prompt();
                    deferredInstall.userChoice.then(function (choice) {
                        if (choice.outcome === 'accepted') card.style.display = 'none';
                        deferredInstall = null;
                    });
                });
            }
        }

        // iOS: check immediately (no beforeinstallprompt event)
        (function () {
            var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
            var standalone = window.navigator.standalone === true;
            if (isIos && !standalone) {
                var card = document.getElementById('install-card');
                var body = document.getElementById('install-body');
                card.style.display = 'block';
                body.textContent = 'In Safari, tap the Share button then "Add to Home Screen" to connect with one tap next time.';
            }
        })();

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
        }
    </script>
</body>
</html>
