@props(['brand' => null])

@php
    $primaryColor = $brand?->brand_color ?? '#007AFE';
    $brandName    = $brand?->brand_name  ?? 'HiFastLink';
    $tagline      = $brand?->brand_tagline ?? 'Connect to the Future';
    $logoUrl      = $brand?->brand_logo    ? Storage::url($brand->brand_logo)    : null;
    $faviconUrl   = $brand?->brand_favicon ? Storage::url($brand->brand_favicon) : null;
    $isBranded    = $brand && $brand->brand_name;
    $layout       = $brand?->brand_layout ?? 'card';
    $bgColor      = $brand?->brand_bg_color ?: null;

    // Convert primary color hex → RGB components for rgba() usage
    $hex = ltrim($primaryColor, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $primary10 = "rgba({$r},{$g},{$b},0.10)";
    $primary15 = "rgba({$r},{$g},{$b},0.15)";
    $primary20 = "rgba({$r},{$g},{$b},0.20)";

    // Background for page (respects custom brand_bg_color where applicable)
    $pageBg = $bgColor ? "background-color: {$bgColor};" : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $primaryColor }}">

    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" href="/favicon.ico">
    @endif

    <title>{{ $brandName }} — WiFi Login</title>

    <style>
        :root { --captive-primary: {{ $primaryColor }}; }
        .captive-primary-bg    { background-color: var(--captive-primary) !important; }
        .captive-primary-text  { color: var(--captive-primary) !important; }
        .captive-primary-ring  { --tw-ring-color: color-mix(in srgb, var(--captive-primary) 30%, transparent) !important; }
        .captive-primary-border { border-color: var(--captive-primary) !important; }
    </style>

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    @livewireStyles
</head>

<body class="font-sans antialiased">

{{-- ============================================================
     Shared logo partial (used inline per layout below)
     ============================================================ --}}

@if ($layout === 'split')

    {{-- ============================================================
         SPLIT — Brand panel (left/top) · White form panel (right/bottom)
         ============================================================ --}}
    <div class="min-h-screen flex flex-col md:flex-row">

        {{-- Brand panel --}}
        <div class="relative md:w-2/5 flex flex-col items-center justify-center py-14 px-8 overflow-hidden"
             style="{{ $pageBg ?? "background-color: {$primaryColor};" }} min-height: 44vw;">
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -top-20 -left-20 w-72 h-72 rounded-full bg-white opacity-5"></div>
                <div class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full bg-white opacity-5"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full bg-white opacity-5 blur-2xl"></div>
            </div>

            <div class="relative z-10 flex flex-col items-center text-center">
                <div class="mb-6">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-24 w-auto object-contain drop-shadow-lg">
                    @else
                        <div class="w-24 h-24 bg-white/20 rounded-3xl flex items-center justify-center shadow-2xl">
                            <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="width:52px;height:52px;">
                                <g transform="translate(73, 0)">
                                    <path fill="white" d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z"/>
                                    <path fill="rgba(255,255,255,0.5)" d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z"/>
                                </g>
                            </svg>
                        </div>
                    @endif
                </div>
                <h1 class="text-white text-4xl font-black mb-3 leading-tight">{{ $brandName }}</h1>
                <p class="text-white/75 text-sm max-w-xs leading-relaxed">{{ $tagline }}</p>
            </div>
        </div>

        {{-- Form panel --}}
        <div class="flex-1 bg-white flex flex-col items-center justify-center py-12 px-8" style="min-height: 56vw;">
            <div class="w-full max-w-sm">
                {{ $slot }}
            </div>
            <div class="mt-10 text-center">
                @if($isBranded)
                    <p class="text-xs text-gray-400">WiFi powered by <a href="https://hifastlink.com" target="_blank" class="font-semibold text-gray-500 hover:underline">HiFastLink</a></p>
                @else
                    <p class="text-xs text-gray-400">© {{ date('Y') }} HiFastLink. Powered by Satellite Technology</p>
                @endif
            </div>
        </div>

    </div>

@elseif ($layout === 'minimal')

    {{-- ============================================================
         MINIMAL — Very dark background · Left-accented white card
         ============================================================ --}}
    <div class="relative min-h-screen flex flex-col items-center justify-center p-5 overflow-hidden"
         style="{{ $pageBg ?? 'background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);' }}">

        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 rounded-full blur-3xl"
                 style="background-color: {{ $primaryColor }}; opacity: 0.12; transform: translate(35%, -35%);"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full blur-3xl"
                 style="background-color: {{ $primaryColor }}; opacity: 0.08; transform: translate(-35%, 35%);"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm">
            <div style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 32px 64px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05); border-left:5px solid {{ $primaryColor }};">

                <div class="flex items-center gap-4 px-7 py-6" style="border-bottom:1px solid #f1f5f9;">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-12 w-auto object-contain flex-shrink-0">
                    @else
                        <div class="flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                             style="background-color: {{ $primary15 }};">
                            <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px;">
                                <g transform="translate(73, 0)">
                                    <path fill="{{ $primaryColor }}" d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z"/>
                                    <path fill="#BED4FE" d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z"/>
                                </g>
                            </svg>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h1 class="font-black text-gray-900 text-lg leading-tight truncate">{{ $brandName }}</h1>
                        <p class="text-gray-400 text-xs leading-snug mt-0.5">{{ $tagline }}</p>
                    </div>
                </div>

                <div class="px-7 py-8">{{ $slot }}</div>
            </div>

            <div class="text-center mt-6">
                @if($isBranded)
                    <p class="text-xs" style="color:rgba(255,255,255,0.35);">WiFi powered by
                        <a href="https://hifastlink.com" target="_blank"
                           style="color:rgba(255,255,255,0.55);font-weight:600;"
                           onmouseover="this.style.color='rgba(255,255,255,0.8)'"
                           onmouseout="this.style.color='rgba(255,255,255,0.55)'">HiFastLink</a>
                    </p>
                @else
                    <p class="text-xs" style="color:rgba(255,255,255,0.35);">© {{ date('Y') }} HiFastLink. Powered by Satellite Technology</p>
                @endif
            </div>
        </div>
    </div>

@elseif ($layout === 'glass')

    {{-- ============================================================
         GLASS — Light tinted background · Frosted glass card
         ============================================================ --}}
    <div class="relative min-h-screen flex flex-col items-center justify-center p-5 overflow-hidden"
         style="{{ $pageBg ?? "background: radial-gradient(ellipse at top right, {$primary20} 0%, transparent 55%), radial-gradient(ellipse at bottom left, {$primary15} 0%, transparent 55%), #f0f4ff;" }}">

        {{-- Decorative blobs --}}
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-16 -right-16 w-80 h-80 rounded-full blur-3xl animate-pulse"
                 style="background-color:{{ $primaryColor }};opacity:0.12;"></div>
            <div class="absolute -bottom-16 -left-16 w-64 h-64 rounded-full blur-3xl animate-pulse"
                 style="background-color:{{ $primaryColor }};opacity:0.08;animation-delay:1.5s;"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm">

            {{-- Frosted glass card --}}
            <div style="background:rgba(255,255,255,0.72);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.6);border-radius:24px;box-shadow:0 8px 32px rgba(0,0,0,0.12),0 1px 2px rgba(0,0,0,0.08);overflow:hidden;">

                {{-- Brand header --}}
                <div class="px-8 pt-8 pb-6 text-center" style="border-bottom:1px solid rgba(0,0,0,0.06);">
                    <div class="flex justify-center mb-4">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-16 w-auto object-contain drop-shadow">
                        @else
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-inner"
                                 style="background:{{ $primary15 }};">
                                <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="width:36px;height:36px;">
                                    <g transform="translate(73, 0)">
                                        <path fill="{{ $primaryColor }}" d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z"/>
                                        <path fill="#BED4FE" d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z"/>
                                    </g>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <h1 class="font-black text-gray-900 text-2xl leading-tight">{{ $brandName }}</h1>
                    <p class="text-gray-500 text-xs mt-1">{{ $tagline }}</p>
                </div>

                {{-- Form --}}
                <div class="px-8 py-8">{{ $slot }}</div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-6">
                @if($isBranded)
                    <p class="text-xs text-slate-500">WiFi powered by <a href="https://hifastlink.com" target="_blank" class="font-semibold text-slate-600 hover:underline">HiFastLink</a></p>
                @else
                    <p class="text-xs text-slate-400">© {{ date('Y') }} HiFastLink. Powered by Satellite Technology</p>
                @endif
            </div>
        </div>
    </div>

@elseif ($layout === 'topbar')

    {{-- ============================================================
         TOPBAR — Full-width brand nav bar · Light body · Form card
         ============================================================ --}}
    <div class="min-h-screen flex flex-col" style="{{ $pageBg ?? 'background-color:#f1f5f9;' }}">

        {{-- Navigation bar --}}
        <div style="background-color:{{ $primaryColor }};">
            <div class="max-w-5xl mx-auto px-6 py-4 flex items-center gap-4">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brandName }}"
                         class="h-10 w-auto object-contain flex-shrink-0 drop-shadow">
                @else
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-white/20">
                        <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;">
                            <g transform="translate(73, 0)">
                                <path fill="white" d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z"/>
                                <path fill="rgba(255,255,255,0.5)" d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z"/>
                            </g>
                        </svg>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-white font-black text-xl leading-tight truncate">{{ $brandName }}</p>
                    <p class="text-white/65 text-xs hidden sm:block truncate">{{ $tagline }}</p>
                </div>
                <div class="hidden md:block">
                    <i class="fa-solid fa-wifi text-white/40 text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 flex flex-col items-center justify-center py-12 px-5">

            {{-- Tagline on mobile (below bar) --}}
            <p class="sm:hidden text-center text-gray-400 text-sm mb-8 px-4">{{ $tagline }}</p>

            {{-- Form card --}}
            <div class="w-full max-w-sm bg-white rounded-2xl shadow-lg overflow-hidden"
                 style="border-top:4px solid {{ $primaryColor }};">
                <div class="px-8 py-8">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-8">
                @if($isBranded)
                    <p class="text-xs text-gray-400">WiFi powered by <a href="https://hifastlink.com" target="_blank" class="font-semibold text-gray-500 hover:underline">HiFastLink</a></p>
                @else
                    <p class="text-xs text-gray-400">© {{ date('Y') }} HiFastLink. Powered by Satellite Technology</p>
                @endif
            </div>
        </div>
    </div>

@else

    {{-- ============================================================
         CARD (default) — Pastel gradient · Overlapping brand header card
         ============================================================ --}}
    <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
         style="{{ $pageBg ?? "background: linear-gradient(135deg, {$primary10} 0%, #f5f3ff 50%, #fdf2f8 100%);" }}">

        {{-- Background circles --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full blur-3xl opacity-20 animate-pulse"
                 style="background-color:{{ $primaryColor }}"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full blur-3xl opacity-20 animate-pulse"
                 style="background-color:{{ $primaryColor }};animation-delay:1s;"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">

                {{-- Brand header --}}
                <div class="px-8 pt-12 pb-24 relative overflow-hidden"
                     style="background-color:{{ $primaryColor }}">
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full blur-2xl animate-pulse"></div>
                        <div class="absolute bottom-10 right-10 w-40 h-40 bg-white rounded-full blur-2xl animate-pulse"
                             style="animation-delay:0.5s;"></div>
                    </div>

                    <div class="flex justify-center mb-6 relative z-10">
                        <div class="w-24 h-24 bg-white rounded-3xl shadow-2xl flex items-center justify-center">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-14 w-auto object-contain">
                            @else
                                <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" style="width:56px;height:56px;">
                                    <g transform="translate(73, 0)">
                                        <path fill="{{ $primaryColor }}" d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z"/>
                                        <path fill="#BED4FE" d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z"/>
                                    </g>
                                </svg>
                            @endif
                        </div>
                    </div>

                    <div class="text-center relative z-10">
                        <h1 class="text-white text-3xl font-black mb-2">{{ $brandName }}</h1>
                        <p class="text-white/80 text-sm">{{ $tagline }}</p>
                    </div>
                </div>

                {{-- Form area --}}
                <div class="bg-white -mt-16 rounded-tr-[3rem] relative z-10 px-8 pt-12 pb-10">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-6 text-gray-500">
                @if($isBranded)
                    <p class="text-xs">WiFi powered by <a href="https://hifastlink.com" target="_blank" class="font-semibold text-gray-600 hover:underline">HiFastLink</a></p>
                @else
                    <p class="text-sm">© {{ date('Y') }} HiFastLink. Powered by Satellite Technology</p>
                @endif
            </div>
        </div>
    </div>

@endif

@livewireScripts

<style>[x-cloak] { display: none !important; }</style>
</body>
</html>
