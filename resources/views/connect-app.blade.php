<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="HiFastLink">
    <meta name="theme-color" content="#007AFE">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/pwa-icon.svg">
    <title>HiFastLink</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            min-height: 100vh; min-height: 100dvh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, #e8f0fe 0%, #f5f0ff 50%, #fce8f0 100%);
            padding: 24px 20px;
        }
        .card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 40px rgba(0,122,254,.12);
            padding: 40px 28px 36px;
            width: 100%; max-width: 360px;
            text-align: center;
        }
        .logo {
            width: 72px; height: 72px;
            margin: 0 auto 18px;
        }
        h1 { font-size: 21px; font-weight: 800; color: #111; margin-bottom: 4px; }
        .tagline { font-size: 13px; color: #9ca3af; margin-bottom: 32px; }

        .state { display: none; }
        .state.active { display: block; }

        .spinner {
            width: 48px; height: 48px;
            border: 3px solid #e5e7eb;
            border-top-color: #007AFE;
            border-radius: 50%;
            animation: spin .85s linear infinite;
            margin: 4px auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            font-size: 30px;
        }
        .icon-green { background: #dcfce7; }
        .icon-gray  { background: #f3f4f6; }

        .s-title { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 8px; }
        .s-body  { font-size: 13px; color: #6b7280; line-height: 1.6; margin-bottom: 24px; }

        .btn {
            display: block; width: 100%;
            background: #007AFE; color: #fff;
            border: none; border-radius: 14px;
            font-size: 15px; font-weight: 600;
            padding: 14px 20px; cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn:active { opacity: .82; }
        .btn-outline {
            background: transparent;
            border: 1.5px solid #e5e7eb;
            color: #374151;
            margin-top: 10px;
        }

        .install-hint {
            margin-top: 24px;
            background: #f0f7ff;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 12px; color: #374151;
            line-height: 1.5; text-align: left;
        }
        .install-hint strong { display: block; color: #007AFE; font-size: 13px; margin-bottom: 4px; }
        #install-btn { display: none; margin-top: 10px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" width="72" height="72">
            <rect width="316" height="316" rx="72" fill="#007AFE"/>
            <g transform="translate(73, 17)">
                <path fill="#ffffff" d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z"/>
                <path fill="#BED4FE" d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z"/>
            </g>
        </svg>
    </div>
    <h1>HiFastLink</h1>
    <p class="tagline">Fast · Reliable · Satellite-powered</p>

    {{-- Checking / auto-connecting --}}
    <div id="s-checking" class="state active">
        <div class="spinner"></div>
        <p class="s-title" id="checking-title">Connecting…</p>
        <p class="s-body" id="checking-body">Checking your connection status.</p>
    </div>

    {{-- Already online --}}
    <div id="s-connected" class="state">
        <div class="icon icon-green">✓</div>
        <p class="s-title">You're online!</p>
        <p class="s-body">HiFastLink is active on this device.</p>
        <a href="https://hifastlink.com/dashboard" class="btn">Go to Dashboard</a>

        <div class="install-hint" id="install-area" style="display:none">
            <strong>📲 Save for next time</strong>
            <span id="ios-hint">Tap the <b>Share</b> icon in Safari, then <b>"Add to Home Screen"</b> — connect with one tap next time.</span>
            <button id="install-btn" class="btn">Install App</button>
        </div>
    </div>

    {{-- Not on any hotspot --}}
    <div id="s-offline" class="state">
        <div class="icon icon-gray">📶</div>
        <p class="s-title">Not on HiFastLink WiFi</p>
        <p class="s-body">Connect to a HiFastLink network first, then open this app and you'll be online automatically.</p>
        <button class="btn btn-outline" onclick="recheck()">Try Again</button>
    </div>
</div>

<script>
    var deferredInstall = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredInstall = e;
    });

    function showState(id) {
        document.querySelectorAll('.state').forEach(function (el) { el.classList.remove('active'); });
        document.getElementById(id).classList.add('active');
    }

    function checkInternet() {
        return new Promise(function (resolve) {
            var img = new Image();
            var tid = setTimeout(function () { img.src = ''; resolve(false); }, 5000);
            img.onload  = function () { clearTimeout(tid); resolve(true); };
            img.onerror = function () { clearTimeout(tid); resolve(false); };
            img.src = 'https://www.google.com/favicon.ico?' + Date.now();
        });
    }

    function checkHotspot() {
        return fetch('/api/ping', { method: 'GET', cache: 'no-store' })
            .then(function (r) { return r.ok; })
            .catch(function () { return false; });
    }

    async function run() {
        var online = await checkInternet();
        if (online) {
            showState('s-connected');
            showInstallHint();
            return;
        }

        var onHotspot = await checkHotspot();
        if (!onHotspot) {
            showState('s-offline');
            return;
        }

        // On the hotspot but not authenticated — trigger MikroTik captive redirect automatically.
        document.getElementById('checking-title').textContent = 'Getting you online…';
        document.getElementById('checking-body').textContent = 'Opening the HiFastLink login. This takes a moment.';
        setTimeout(function () {
            window.location.href = 'http://detectportal.firefox.com/';
        }, 600);
    }

    function recheck() {
        showState('s-checking');
        document.getElementById('checking-title').textContent = 'Connecting…';
        document.getElementById('checking-body').textContent = 'Checking your connection status.';
        run();
    }

    function showInstallHint() {
        var standalone = window.matchMedia('(display-mode: standalone)').matches
                      || window.navigator.standalone === true;
        if (standalone) return;

        var area = document.getElementById('install-area');
        area.style.display = 'block';

        if (/iphone|ipad|ipod/i.test(navigator.userAgent)) {
            document.getElementById('ios-hint').style.display = 'inline';
        } else if (deferredInstall) {
            document.getElementById('ios-hint').style.display = 'none';
            var btn = document.getElementById('install-btn');
            btn.style.display = 'block';
            btn.addEventListener('click', function () {
                deferredInstall.prompt();
                deferredInstall.userChoice.then(function (c) {
                    if (c.outcome === 'accepted') area.style.display = 'none';
                    deferredInstall = null;
                });
            });
        } else {
            area.style.display = 'none';
        }
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
    }

    run();
</script>
</body>
</html>
