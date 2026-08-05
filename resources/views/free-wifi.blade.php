<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#007AFE">
    <title>Free WiFi — {{ $router->name }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #007AFE;
            --blue-dk: #0057c8;
            --sky:     #BAD9FC;
            --navy:    #0B1628;
            --bg:      #f0f5ff;
            --card:    #ffffff;
            --text:    #0d1b2e;
            --muted:   #5a6a84;
            --border:  #d8e4f5;
            --input:   #f5f8ff;
            --err:     #dc2626;
            --success-bg: #ecfdf5;
            --success-text: #065f46;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg:    #05111f;
                --card:  #0e1e30;
                --text:  #e8f1ff;
                --muted: #7b9bbf;
                --border:#1e3450;
                --input: #0a1828;
                --success-bg: #052e1f;
                --success-text: #6ee7b7;
            }
        }

        :root[data-theme="light"] {
            --bg: #f0f5ff; --card: #ffffff; --text: #0d1b2e;
            --muted: #5a6a84; --border: #d8e4f5; --input: #f5f8ff;
            --success-bg: #ecfdf5; --success-text: #065f46;
        }
        :root[data-theme="dark"] {
            --bg: #05111f; --card: #0e1e30; --text: #e8f1ff;
            --muted: #7b9bbf; --border: #1e3450; --input: #0a1828;
            --success-bg: #052e1f; --success-text: #6ee7b7;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 16px 40px;
        }

        /* ── Card ── */
        .card {
            width: 100%;
            max-width: 400px;
            background: var(--card);
            border-radius: 28px;
            box-shadow: 0 12px 48px rgba(0,90,200,.13);
            overflow: hidden;
        }

        /* ── Hero stripe ── */
        .hero {
            background: linear-gradient(135deg, #0057c8 0%, #007AFE 60%, #38a0ff 100%);
            padding: 32px 28px 28px;
            text-align: center;
            position: relative;
        }

        /* wifi wave SVG background */
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='100' cy='160' r='15' fill='none' stroke='white' stroke-width='1.5' opacity='.15'/%3E%3Ccircle cx='100' cy='160' r='40' fill='none' stroke='white' stroke-width='1.5' opacity='.12'/%3E%3Ccircle cx='100' cy='160' r='65' fill='none' stroke='white' stroke-width='1.5' opacity='.09'/%3E%3Ccircle cx='100' cy='160' r='90' fill='none' stroke='white' stroke-width='1.5' opacity='.06'/%3E%3Ccircle cx='100' cy='160' r='115' fill='none' stroke='white' stroke-width='1.5' opacity='.04'/%3E%3C/svg%3E") center/220px no-repeat;
            pointer-events: none;
        }

        .logo-wrap {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            position: relative;
        }

        .badge-free {
            display: inline-block;
            background: #F5A224;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 100px;
            margin-bottom: 12px;
            position: relative;
        }

        .hero h1 {
            font-size: 26px;
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            text-wrap: balance;
            margin-bottom: 8px;
            position: relative;
        }

        .hero p {
            font-size: 13px;
            color: rgba(255,255,255,.78);
            line-height: 1.5;
            position: relative;
        }

        .offer-pill {
            display: inline-block;
            margin-top: 14px;
            background: rgba(255,255,255,.18);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            position: relative;
        }

        /* ── Form body ── */
        .body {
            padding: 28px 24px;
        }

        .section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .field input {
            display: block;
            width: 100%;
            background: var(--input);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 15px;
            color: var(--text);
            outline: none;
            transition: border-color .15s;
        }

        .field input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(0,122,254,.12);
        }

        .field input::placeholder { color: var(--muted); opacity: .6; }

        .error-msg {
            font-size: 12px;
            color: var(--err);
            margin-top: 5px;
        }

        .btn-primary {
            display: block;
            width: 100%;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: background .15s, transform .1s;
        }

        .btn-primary:active { background: var(--blue-dk); transform: scale(.98); }
        .btn-primary:disabled { opacity: .55; cursor: not-allowed; }

        .fine-print {
            font-size: 11px;
            color: var(--muted);
            text-align: center;
            margin-top: 14px;
            line-height: 1.5;
        }

        /* ── Already claimed notice ── */
        .notice {
            background: var(--input);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .notice-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #fef3c7;
            color: #92400e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .notice-title { font-size: 13px; font-weight: 700; margin-bottom: 3px; }
        .notice-body  { font-size: 12px; color: var(--muted); }

        /* ── Info chips ── */
        .chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
            justify-content: center;
        }

        .chip {
            background: rgba(0,122,254,.1);
            color: var(--blue);
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 100px;
        }

        @media (prefers-color-scheme: dark) {
            .chip { background: rgba(0,122,254,.2); }
            .notice-icon { background: #422006; color: #fbbf24; }
        }

        /* spinner */
        .spinner {
            width: 20px; height: 20px;
            border: 2.5px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="card">
    {{-- Hero --}}
    <div class="hero">
        <div class="logo-wrap">
            <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" width="40" height="40">
                <rect width="316" height="316" rx="72" fill="rgba(255,255,255,.15)"/>
                <path fill="#fff" d="M 35,210 L 158,68 L 281,210 L 281,172 L 158,106 L 35,172 Z"/>
                <path fill="rgba(255,255,255,.5)" d="M 35,298 L 158,156 L 281,298 L 281,260 L 158,194 L 35,260 Z"/>
            </svg>
            <span style="color:#fff;font-weight:800;font-size:17px;letter-spacing:-.01em">HiFastLink</span>
        </div>

        <div class="badge-free">✦ Free Offer</div>

        <h1>2 Days of Free<br>Internet Access</h1>
        <p>Register once. Connect immediately.<br>No credit card needed.</p>

        <div>
            <span class="offer-pill">📶 {{ $router->name }}</span>
        </div>
    </div>

    {{-- Body --}}
    <div class="body">

        @if(! $enabled)
        <div class="notice">
            <div class="notice-icon">📵</div>
            <div>
                <div class="notice-title">Offer not available right now</div>
                <div class="notice-body">The free trial is temporarily paused. Check back soon, or <a href="{{ route('login') }}" style="color:var(--blue);font-weight:600;">log in</a> if you already have an account.</div>
            </div>
        </div>
        @elseif($errors->has('phone') && str_contains($errors->first('phone'), 'already used'))
        {{-- Already claimed state --}}
        <div class="notice">
            <div class="notice-icon">⚠️</div>
            <div>
                <div class="notice-title">Trial already claimed</div>
                <div class="notice-body">{{ $errors->first('phone') }}<br>If you have an account, please <a href="{{ route('login') }}" style="color: var(--blue); font-weight: 600;">log in here</a>.</div>
            </div>
        </div>
        @elseif($errors->any())
        <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:13px;color:#991b1b;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        @if($enabled)
        <p class="section-label">Create your account — takes 10 seconds</p>

        <form method="POST" action="{{ route('free-wifi.register', $router->nas_identifier) }}" id="reg-form">
            @csrf

            @if($mac)
                <input type="hidden" name="mac" value="{{ $mac }}">
            @endif

            <div class="field">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name"
                    value="{{ old('name') }}"
                    placeholder="e.g. Chidi Okeke"
                    autocomplete="name"
                    required>
            </div>

            <div class="field">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                    value="{{ old('phone') }}"
                    placeholder="e.g. 08012345678"
                    autocomplete="tel"
                    inputmode="tel"
                    required>
                <div class="error-msg">{{ $errors->first('phone') ?? '' }}</div>
            </div>

            <button type="submit" class="btn-primary" id="submit-btn">
                Get 2 Days Free Internet →
            </button>
        </form>

        <div class="chips">
            <span class="chip">✓ Unlimited Data</span>
            <span class="chip">✓ 48 Hours</span>
            <span class="chip">✓ One Per Number</span>
        </div>

        <p class="fine-print">
            By registering you agree to HiFastLink's fair use policy.<br>
            Already have an account? <a href="{{ route('login') }}" style="color:var(--blue);font-weight:600;">Sign in</a>
        </p>
        @endif
    </div>
</div>

<script>
    document.getElementById('reg-form').addEventListener('submit', function () {
        var btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>Connecting…';
    });
</script>
</body>
</html>
