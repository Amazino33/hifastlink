@php
    // The app clone's .env has APP_URL=https://app.hifastlink.com
    // The main clone's .env has APP_URL=https://hifastlink.com
    // Use this to serve the right theme from the same shared codebase.
    $isApp = str_contains(config('app.url'), 'app.hifastlink');
@endphp

@if($isApp)
{{-- ═══════════════════════════════════════════════════════════════════
     CUSTOMER PWA  —  dark navy theme matching the app shell
     app.hifastlink.com
═══════════════════════════════════════════════════════════════════ --}}
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

        .auth-shell {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            padding-top:    env(safe-area-inset-top,    0px);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        .auth-hdr {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--border-l);
            flex-shrink: 0;
        }
        .auth-hdr-logo {
            width: 38px; height: 38px;
            border-radius: 11px;
            background: rgba(255,255,255,0.07);
            padding: 5px;
            object-fit: contain;
        }
        .auth-hdr-name {
            font-family: 'Sora', sans-serif;
            font-size: 18px; font-weight: 700;
            color: var(--txt);
            line-height: 1.1; letter-spacing: -0.3px;
        }
        .auth-hdr-tag {
            font-size: 10.5px; color: var(--txt-3);
            display: block; margin-top: 1px;
        }

        .auth-body {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 20px 20px 16px;
        }

        .auth-foot {
            text-align: center;
            padding: 10px 20px;
            font-size: 11.5px;
            color: var(--txt-3);
            flex-shrink: 0;
        }

        /* ── Dark-skin overrides for the light Tailwind utilities ── */
        .auth-wrap .bg-gray-50                     { background-color: rgba(255,255,255,0.05) !important; }
        .auth-wrap .bg-gray-100                    { background-color: rgba(255,255,255,0.055) !important; }
        .auth-wrap .bg-blue-50                     { background-color: rgba(10,132,255,0.09) !important; }

        /* bg-white by element type */
        .auth-wrap span.bg-white                   { background-color: var(--bg) !important; }
        .auth-wrap button.bg-white                 { background-color: rgba(255,255,255,0.11) !important; }
        .auth-wrap a.bg-white                      { background-color: var(--surf-1) !important; }
        .auth-wrap div.bg-white                    { background-color: var(--surf-1) !important; }
        .auth-wrap a.hover\:bg-gray-50:hover       { background-color: var(--surf-2) !important; }
        .auth-wrap .hover\:bg-gray-50:hover        { background-color: rgba(255,255,255,0.04) !important; }

        .auth-wrap .border-gray-200                { border-color: var(--border-l) !important; }
        .auth-wrap .border-gray-300                { border-color: var(--border-m) !important; }
        .auth-wrap .border-blue-200                { border-color: rgba(10,132,255,0.25) !important; }
        .auth-wrap .border-t-2                     { border-top-color: var(--border-l) !important; }

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

        .auth-wrap input::placeholder,
        .auth-wrap textarea::placeholder           { color: var(--txt-3) !important; }
        .auth-wrap .focus\:bg-white:focus          { background-color: rgba(255,255,255,0.08) !important; }
        .auth-wrap .focus\:border-blue-500:focus,
        .auth-wrap .focus\:border-primary:focus    { border-color: var(--accent) !important; }
        .auth-wrap .focus\:ring-4:focus            { box-shadow: 0 0 0 3px var(--accent-d) !important; }
        .auth-wrap .focus\:ring-blue-100:focus     { --tw-ring-color: var(--accent-d) !important; }

        .auth-wrap input[type="checkbox"] {
            background-color: rgba(255,255,255,0.06) !important;
            border-color: var(--border-m) !important;
        }

        .auth-wrap .ring-4.ring-red-100            { box-shadow: 0 0 0 3px rgba(239,68,68,0.2) !important; }
        .auth-wrap .text-green-600                 { color: #34d399 !important; }
        .auth-wrap .bg-green-100                   { background-color: rgba(52,211,153,0.1) !important; }
        .auth-wrap .border-green-400               { border-color: rgba(52,211,153,0.3) !important; }
        .auth-wrap .text-green-700                 { color: #34d399 !important; }

        /* Compact layout — sign-in fits on screen without scrolling */
        .auth-wrap .text-4xl                       { font-size: 1.45rem !important; font-weight: 800 !important; }
        .auth-wrap .mb-6                           { margin-bottom: 0.9rem !important; }
        .auth-wrap .mb-3                           { margin-bottom: 0.4rem !important; }
        .auth-wrap .py-4                           { padding-top: 0.72rem !important; padding-bottom: 0.72rem !important; }
        .auth-wrap .p-4                            { padding: 0.65rem 0.875rem !important; }
        .auth-wrap .space-y-6 > * + *             { margin-top: 0.875rem !important; }
        .auth-wrap .space-y-5 > * + *             { margin-top: 0.75rem !important; }
    </style>
</head>
<body>
<div class="auth-shell">
    <div class="auth-hdr">
        <a href="{{ url('/') }}" style="display:flex;align-items:center;gap:11px;text-decoration:none;">
            <img src="/logo.png" alt="HiFastLink" class="auth-hdr-logo">
            <div>
                <span class="auth-hdr-name">HiFastLink</span>
                <span class="auth-hdr-tag">Connect to the Future</span>
            </div>
        </a>
    </div>
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

@else
{{-- ═══════════════════════════════════════════════════════════════════
     MAIN SITE  —  original light theme
     hifastlink.com
═══════════════════════════════════════════════════════════════════ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#007AFE">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="HiFastLink">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    @livewireStyles
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Animated background circles -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-200 rounded-full blur-3xl opacity-30 animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-pink-200 rounded-full blur-3xl opacity-20 animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden transform hover:scale-[1.01] transition-transform duration-300">

                <!-- Gradient Header with Logo -->
                <div class="bg-background px-8 pt-12 pb-24 relative overflow-hidden" style="background-image: url('{{ asset('images/background.png') }}'); background-size: cover; background-position: center; background-blend-mode: multiply;">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 w-full h-full">
                            <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full blur-2xl animate-pulse"></div>
                            <div class="absolute bottom-10 right-10 w-40 h-40 bg-yellow-300 rounded-full blur-2xl animate-pulse" style="animation-delay: 0.5s;"></div>
                        </div>
                    </div>
                    <a href="{{ route('home') }}" class="flex justify-center mb-6 relative z-10">
                        <div class="w-24 h-24 bg-white rounded-3xl shadow-2xl flex items-center justify-center transform hover:rotate-6 transition-transform duration-300 group p-3">
                            <img src="/logo.png" alt="HiFastLink" class="w-full h-full object-contain">
                        </div>
                    </a>
                    <div class="text-center relative z-10">
                        <h1 class="text-white text-3xl font-black mb-2">HiFastLink</h1>
                        <p class="text-blue-100 text-sm">Connect to the Future</p>
                    </div>
                </div>

                <!-- Form Section with Curved Overlay -->
                <div class="bg-white -mt-16 rounded-tr-[3rem] relative z-10 px-8 pt-12 pb-10">
                    {{ $slot }}
                </div>
            </div>

            <div class="text-center mt-8 text-gray-600">
                <p class="text-sm">© {{ date('Y') }} HiFastLink. Powered by Satellite Technology</p>
            </div>
        </div>
    </div>

    @livewireScripts

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>
@endif
