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
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f0fe 0%, #f5f0ff 50%, #fce8f0 100%);
            padding: 24px 20px;
        }
        .card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 40px rgba(0,122,254,.12);
            padding: 40px 28px 36px;
            width: 100%;
            max-width: 360px;
            text-align: center;
        }
        .logo {
            width: 72px; height: 72px;
            background: #007AFE;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            font-size: 36px; font-weight: 800;
            color: #fff;
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
        .icon-blue  { background: #dbeafe; }
        .icon-gray  { background: #f3f4f6; }

        .s-title { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 8px; }
        .s-body  { font-size: 13px; color: #6b7280; line-height: 1.6; margin-bottom: 24px; }

        .btn {
            display: block; width: 100%;
            background: #007AFE; color: #fff;
            border: none; border-radius: 14px;
            font-size: 15px; font-weight: 600;
            padding: 14px 20px;
            cursor: pointer;
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
            font-size: 12px;
            color: #374151;
            line-height: 1.5;
            text-align: left;
        }
        .install-hint strong { display: block; color: #007AFE; font-size: 13px; margin-bottom: 4px; }
        #install-btn { display: none; margin-top: 10px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">H</div>
    <h1>HiFastLink</h1>
    <p class="tagline">Fast · Reliable · Satellite-powered</p>

    {{-- checking --}}
    <div id="s-checking" class="state active">
        <div class="spinner"></div>
        <p class="s-title">Checking connection…</p>
    </div>

    {{-- already online --}}
    <div id="s-connected" class="state">
        <div class="icon icon-green">✓</div>
        <p class="s-title">You're online!</p>
        <p class="s-body">HiFastLink is active on this device.</p>
        <a href="https://hifastlink.com/dashboard" class="btn">Go to Dashboard</a>

        <div class="install-hint" id="install-area" style="display:none">
            <strong>📲 Save to your home screen</strong>
            <span id="ios-hint">Tap the <b>Share</b> icon in Safari, then <b>"Add to Home Screen"</b> — connect with one tap next time.</span>
            <button id="install-btn" class="btn">Install App</button>
        </div>
    </div>

    {{-- on hotspot but not authenticated — manual tap --}}
    <div id="s-needs-tap" class="state">
        <div class="icon icon-blue">⚡</div>
        <p class="s-title">Ready to connect</p>
        <p class="s-body">You're on the HiFastLink network. Tap below and you'll be online in seconds.</p>
        <button class="btn" onclick="startConnect()">Connect Now</button>
    </div>

    {{-- navigating to trigger captive portal --}}
    <div id="s-connecting" class="state">
        <div class="spinner"></div>
        <p class="s-title">Connecting…</p>
        <p class="s-body">Opening the HiFastLink login. This takes a moment.</p>
    </div>

    {{-- not on any hotspot --}}
    <div id="s-offline" class="state">
        <div class="icon icon-gray">📶</div>
        <p class="s-title">Not on HiFastLink WiFi</p>
        <p class="s-body">Connect to a HiFastLink network first, then open this app to get online.</p>
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
        document.querySelectorAll('.state').forEach(function (el) {
            el.classList.remove('active');
        });
        document.getElementById(id).classList.add('active');
    }

    // Connectivity check: load a Google asset over HTTPS.
    // On a captive portal, MikroTik DNS-spoofs the domain → HTTPS cert fails → onerror fires.
    function checkInternet() {
        return new Promise(function (resolve) {
            var img = new Image();
            var tid = setTimeout(function () { img.src = ''; resolve(false); }, 5000);
            img.onload  = function () { clearTimeout(tid); resolve(true);  };
            img.onerror = function () { clearTimeout(tid); resolve(false); };
            img.src = 'https://www.google.com/favicon.ico?' + Date.now();
        });
    }

    // Hotspot check: reach our own server via the walled garden (works before auth).
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
        showState(onHotspot ? 's-needs-tap' : 's-offline');
    }

    function startConnect() {
        showState('s-connecting');
        // Navigate to an HTTP URL not in the walled garden.
        // MikroTik intercepts this, redirects to our captive portal with ?mac=...&link-login=...
        // Our controller's MAC auto-login silently bridges the device.
        setTimeout(function () {
            window.location.href = 'http://detectportal.firefox.com/';
        }, 400);
    }

    function recheck() {
        showState('s-checking');
        run();
    }

    function showInstallHint() {
        // Don't show if already running as a standalone PWA
        var standalone = window.matchMedia('(display-mode: standalone)').matches
                      || window.navigator.standalone === true;
        if (standalone) return;

        var area = document.getElementById('install-area');
        area.style.display = 'block';

        var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);

        if (isIos) {
            document.getElementById('ios-hint').style.display = 'inline';
        } else if (deferredInstall) {
            document.getElementById('ios-hint').style.display = 'none';
            var btn = document.getElementById('install-btn');
            btn.style.display = 'block';
            btn.addEventListener('click', function () {
                deferredInstall.prompt();
                deferredInstall.userChoice.then(function (choice) {
                    if (choice.outcome === 'accepted') btn.style.display = 'none';
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
