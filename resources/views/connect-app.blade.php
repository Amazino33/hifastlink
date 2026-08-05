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
    <link rel="manifest" href="/site.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
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
        .logo { width: 72px; height: 72px; margin: 0 auto 18px; }
        h1 { font-size: 21px; font-weight: 800; color: #111; margin-bottom: 4px; }
        .tagline { font-size: 13px; color: #9ca3af; margin-bottom: 32px; }

        .icon {
            width: 64px; height: 64px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 28px;
        }
        .icon-green { background: #dcfce7; }
        .icon-gray  { background: #f3f4f6; }
        .icon-blue  { background: #dbeafe; }

        .status-title { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 8px; }
        .status-body  { font-size: 13px; color: #6b7280; line-height: 1.6; margin-bottom: 24px; }

        .spinner {
            width: 40px; height: 40px;
            border: 3px solid #e5e7eb;
            border-top-color: #007AFE;
            border-radius: 50%;
            animation: spin .85s linear infinite;
            margin: 8px auto 20px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .btn {
            display: block; width: 100%;
            background: #007AFE; color: #fff;
            border: none; border-radius: 14px;
            font-size: 15px; font-weight: 600;
            padding: 15px 20px; cursor: pointer;
            text-decoration: none;
            transition: opacity .15s;
            margin-bottom: 10px;
        }
        .btn:active { opacity: .82; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-outline {
            background: transparent;
            border: 1.5px solid #e5e7eb;
            color: #374151;
        }
        .btn-green { background: #16a34a; }

        .divider {
            font-size: 12px; color: #d1d5db;
            margin: 4px 0 14px;
            position: relative;
        }

        .install-hint {
            margin-top: 20px;
            background: #f0f7ff; border-radius: 14px;
            padding: 14px 16px;
            font-size: 12px; color: #374151; line-height: 1.5; text-align: left;
        }
        .install-hint strong { display: block; color: #007AFE; font-size: 13px; margin-bottom: 4px; }
        #install-btn { display: none; margin-top: 10px; }

        .hidden { display: none !important; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <img src="/logo.png" alt="HiFastLink" style="width:72px;height:72px;object-fit:contain;border-radius:18px;">
    </div>
    <h1>HiFastLink</h1>
    <p class="tagline">Fast · Reliable · Satellite-powered</p>

    {{-- Checking state --}}
    <div id="s-checking">
        <div class="spinner"></div>
        <p class="status-body">Checking your connection…</p>
    </div>

    {{-- Already online --}}
    <div id="s-connected" class="hidden">
        <div class="icon icon-green">✓</div>
        <p class="status-title">You're online!</p>
        <p class="status-body">HiFastLink is active on this device.</p>
        <a href="/dashboard" class="btn btn-green">Go to Dashboard</a>
        <div id="install-area" class="install-hint hidden">
            <strong>📲 Save for next time</strong>
            <span id="ios-hint">Tap the <b>Share</b> icon then <b>"Add to Home Screen"</b> — connect with one tap next time.</span>
            <button id="install-btn" class="btn">Install App</button>
        </div>
    </div>

    {{-- On hotspot, not connected — show action buttons --}}
    <div id="s-hotspot" class="hidden">
        <div class="icon icon-blue">📶</div>
        <p class="status-title">You're on HiFastLink WiFi</p>
        <p class="status-body">Tap below to get online.</p>
        <button id="btn-connect" class="btn" onclick="doConnect()">Connect</button>
        <p class="divider">— or —</p>
        <button class="btn btn-outline" onclick="doVoucher()">Enter voucher / code</button>
    </div>

    {{-- Not on any hotspot --}}
    <div id="s-offline" class="hidden">
        <div class="icon icon-gray">📵</div>
        <p class="status-title">Not on HiFastLink WiFi</p>
        <p class="status-body">Connect to a HiFastLink network first, then open this app.</p>
        <button class="btn btn-outline" onclick="init()">Try Again</button>
    </div>

    {{-- Connecting in progress --}}
    <div id="s-connecting" class="hidden">
        <div class="spinner"></div>
        <p class="status-title" id="connecting-title">Connecting…</p>
        <p class="status-body" id="connecting-body">Opening HiFastLink. Just a moment.</p>
    </div>
</div>

<script>
    var deferredInstall  = null;
    var detectedRouter   = null;
    var detectedLinkLogin = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredInstall = e;
    });

    function show(id) {
        ['s-checking','s-connected','s-hotspot','s-offline','s-connecting'].forEach(function (s) {
            document.getElementById(s).classList.add('hidden');
        });
        document.getElementById(id).classList.remove('hidden');
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
            .then(function (r) { return r.ok ? r.json() : null; })
            .catch(function () { return null; });
    }

    async function init() {
        show('s-checking');

        var online = await checkInternet();
        if (online) {
            show('s-connected');
            showInstallHint();
            return;
        }

        var hotspot = await checkHotspot();
        if (hotspot) {
            detectedRouter    = hotspot.router    || null;
            detectedLinkLogin = hotspot.link_login || null;
            show('s-hotspot');
        } else {
            show('s-offline');
        }
    }

    function doConnect() {
        show('s-connecting');
        document.getElementById('connecting-title').textContent = 'Connecting…';
        document.getElementById('connecting-body').textContent = 'Opening HiFastLink. Just a moment.';

        if (detectedRouter) {
            window.location.href = '/connect-bridge?router=' + encodeURIComponent(detectedRouter);
        } else {
            // Router IP not matched — go to login and let the user connect from dashboard
            window.location.href = '/login';
        }
    }

    function doVoucher() {
        show('s-connecting');
        document.getElementById('connecting-title').textContent = 'Opening login page…';
        document.getElementById('connecting-body').textContent = 'You\'ll be asked to enter your voucher or phone number.';
        window.location.href = 'http://login.wifi';
    }

    function showInstallHint() {
        var standalone = window.matchMedia('(display-mode: standalone)').matches
                      || window.navigator.standalone === true;
        if (standalone) return;

        var area = document.getElementById('install-area');
        area.classList.remove('hidden');

        if (/iphone|ipad|ipod/i.test(navigator.userAgent)) {
            document.getElementById('ios-hint').style.display = 'inline';
        } else if (deferredInstall) {
            document.getElementById('ios-hint').style.display = 'none';
            var btn = document.getElementById('install-btn');
            btn.style.display = 'block';
            btn.addEventListener('click', function () {
                deferredInstall.prompt();
                deferredInstall.userChoice.then(function (c) {
                    if (c.outcome === 'accepted') area.classList.add('hidden');
                    deferredInstall = null;
                });
            });
        } else {
            area.classList.add('hidden');
        }
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
    }

    init();
</script>
</body>
</html>
