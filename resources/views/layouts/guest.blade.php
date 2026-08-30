<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a84ff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="HiFastLink">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/pwa-icon.svg">

    <title>{{ config('app.name', 'HiFastLink') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    @livewireStyles

    <style>
        :root {
            --bg:       #0a0e1a;
            --surf-1:   #131929;
            --surf-2:   #182036;
            --surf-3:   #1e2840;
            --border-l: rgba(255,255,255,0.08);
            --border-m: rgba(255,255,255,0.13);
            --accent:   #0a84ff;
            --accent-d: rgba(10,132,255,0.15);
            --txt:      #e8eeff;
            --txt-2:    rgba(232,238,255,0.55);
            --txt-3:    rgba(232,238,255,0.32);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { height: 100%; background: var(--bg); }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--txt);
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        [x-cloak] { display: none !important; }

        /* ── Shell ──────────────────────────────────────────────────── */
        .auth-shell {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            padding-top:    env(safe-area-inset-top,    0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        /* ── Compact header ─────────────────────────────────────────── */
        .auth-hdr {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--border-l);
            flex-shrink: 0;
        }
        .auth-hdr-logo {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: rgba(255,255,255,0.07);
            padding: 5px;
            object-fit: contain;
        }
        .auth-hdr-name {
            font-family: 'Sora', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--txt);
            line-height: 1.1;
            letter-spacing: -0.3px;
        }
        .auth-hdr-tag {
            font-size: 10.5px;
            color: var(--txt-3);
            display: block;
            margin-top: 1px;
        }

        /* ── Scrollable form area ───────────────────────────────────── */
        .auth-body {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 20px 20px 16px;
        }

        /* ── Footer ─────────────────────────────────────────────────── */
        .auth-foot {
            text-align: center;
            padding: 10px 20px;
            font-size: 11.5px;
            color: var(--txt-3);
            flex-shrink: 0;
        }

        /* ══════════════════════════════════════════════════════════════
           Dark-skin overrides — restyle the light Tailwind utilities
           used inside the auth blade views without touching those files.
        ══════════════════════════════════════════════════════════════ */

        /* ── Surfaces ─── */
        .auth-wrap .bg-gray-50                     { background-color: rgba(255,255,255,0.05) !important; }
        .auth-wrap .bg-gray-100                    { background-color: rgba(255,255,255,0.055) !important; }
        .auth-wrap .bg-blue-50                     { background-color: rgba(10,132,255,0.09) !important; }

        /* bg-white is context-dependent — target by element type */
        .auth-wrap span.bg-white                   { background-color: var(--bg) !important; } /* divider span */
        .auth-wrap button.bg-white                 { background-color: rgba(255,255,255,0.11) !important; } /* active toggle pill */
        .auth-wrap a.bg-white                      { background-color: var(--surf-1) !important; } /* Google button */
        .auth-wrap div.bg-white                    { background-color: var(--surf-1) !important; }
        .auth-wrap a.hover\:bg-gray-50:hover       { background-color: var(--surf-2) !important; }
        .auth-wrap .hover\:bg-gray-50:hover        { background-color: rgba(255,255,255,0.04) !important; }

        /* ── Borders ─── */
        .auth-wrap .border-gray-200                { border-color: var(--border-l) !important; }
        .auth-wrap .border-gray-300                { border-color: var(--border-m) !important; }
        .auth-wrap .border-blue-200                { border-color: rgba(10,132,255,0.25) !important; }
        .auth-wrap .border-t-2                     { border-top-color: var(--border-l) !important; }

        /* ── Text ─── */
        .auth-wrap .text-gray-900                  { color: var(--txt) !important; }
        .auth-wrap .text-gray-800                  { color: var(--txt) !important; }
        .auth-wrap .text-gray-700                  { color: var(--txt-2) !important; }
        .auth-wrap .text-gray-600                  { color: var(--txt-2) !important; }
        .auth-wrap .text-gray-500                  { color: var(--txt-2) !important; }
        .auth-wrap .text-gray-400                  { color: var(--txt-3) !important; }
        .auth-wrap .text-blue-600                  { color: var(--accent) !important; }
        .auth-wrap .text-blue-800                  { color: #5ab4ff !important; }
        .auth-wrap .hover\:text-gray-700:hover     { color: var(--txt) !important; }
        .auth-wrap .hover\:text-gray-900:hover     { color: var(--txt) !important; }

        /* ── Input fields ─── */
        .auth-wrap input::placeholder,
        .auth-wrap textarea::placeholder           { color: var(--txt-3) !important; }
        .auth-wrap .focus\:bg-white:focus          { background-color: rgba(255,255,255,0.08) !important; }
        .auth-wrap .focus\:border-blue-500:focus,
        .auth-wrap .focus\:border-primary:focus    { border-color: var(--accent) !important; }
        .auth-wrap .focus\:ring-4:focus            { box-shadow: 0 0 0 3px var(--accent-d) !important; }
        .auth-wrap .focus\:ring-blue-100:focus     { --tw-ring-color: var(--accent-d) !important; }

        /* ── Checkbox ─── */
        .auth-wrap input[type="checkbox"] {
            background-color: rgba(255,255,255,0.06) !important;
            border-color: var(--border-m) !important;
        }

        /* ── Error ring ─── */
        .auth-wrap .ring-4.ring-red-100            { box-shadow: 0 0 0 3px rgba(239,68,68,0.2) !important; }

        /* ── Green success text ─── */
        .auth-wrap .text-green-600                 { color: #34d399 !important; }

        /* ── Session status box ─── */
        .auth-wrap .bg-green-100                   { background-color: rgba(52,211,153,0.1) !important; }
        .auth-wrap .border-green-400               { border-color: rgba(52,211,153,0.3) !important; }
        .auth-wrap .text-green-700                 { color: #34d399 !important; }

        /* ── Compact layout — reduce vertical footprint ─── */
        .auth-wrap .text-4xl                       { font-size: 1.45rem !important; font-weight: 800 !important; }
        .auth-wrap .mb-6                           { margin-bottom: 0.9rem !important; }
        .auth-wrap .mb-3                           { margin-bottom: 0.4rem !important; }
        .auth-wrap .py-4                           { padding-top: 0.72rem !important; padding-bottom: 0.72rem !important; }
        .auth-wrap .p-4                            { padding: 0.65rem 0.875rem !important; }
        .auth-wrap .space-y-6 > * + *             { margin-top: 0.875rem !important; }
        .auth-wrap .space-y-5 > * + *             { margin-top: 0.75rem !important; }

        /* ── WhatsApp / Livewire OTP component ─── */
        .auth-wrap .bg-green-500                   { background-color: #25d366 !important; }
        .auth-wrap .border-green-500               { border-color: #25d366 !important; }
    </style>
</head>

<body>
<div class="auth-shell">

    <!-- Compact branded header -->
    <div class="auth-hdr">
        <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:11px;text-decoration:none;">
            <img src="/logo.png" alt="HiFastLink" class="auth-hdr-logo">
            <div>
                <span class="auth-hdr-name">HiFastLink</span>
                <span class="auth-hdr-tag">Connect to the Future</span>
            </div>
        </a>
    </div>

    <!-- Scrollable form -->
    <div class="auth-body">
        <div class="auth-wrap">
            {{ $slot }}
        </div>
    </div>

    <div class="auth-foot">© {{ date('Y') }} HiFastLink · Satellite Technology</div>

</div>

@livewireScripts

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }
</script>
</body>
</html>
