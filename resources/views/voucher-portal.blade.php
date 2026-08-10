@php
    $integration = \App\Models\PartnerIntegration::findBySlug($slug ?? request()->route('slug'));
    $color = $integration?->primary_color ?? '#6d28d9';
    // Derive a slightly darker shade for gradients by using the same color twice
    $title    = $integration?->portal_title    ?? $integration?->name    ?? 'Partner';
    $subtitle = $integration?->portal_subtitle ?? 'Wi-Fi Access';
    $icon     = $integration?->icon ?? 'fa-solid fa-wifi';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} Wi-Fi</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden"
        style="background: linear-gradient(135deg, {{ $color }}08, {{ $color }}12, {{ $color }}08)">

        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full blur-3xl opacity-20 animate-pulse"
                style="background-color: {{ $color }}"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full blur-3xl opacity-20 animate-pulse"
                style="background-color: {{ $color }}; animation-delay: 1s;"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">

                <div class="px-8 pt-12 pb-24 relative overflow-hidden"
                    style="background: linear-gradient(135deg, {{ $color }}, {{ $color }}cc)">
                    <div class="absolute inset-0 opacity-10 pointer-events-none">
                        <div class="absolute top-8 left-8 w-32 h-32 bg-white rounded-full blur-2xl animate-pulse"></div>
                        <div class="absolute bottom-8 right-8 w-40 h-40 bg-white rounded-full blur-2xl animate-pulse" style="animation-delay:0.5s"></div>
                    </div>
                    <div class="flex justify-center mb-6 relative z-10">
                        <div class="w-24 h-24 bg-white rounded-3xl shadow-2xl flex items-center justify-center">
                            <i class="{{ $icon }} text-4xl" style="color: {{ $color }}"></i>
                        </div>
                    </div>
                    <div class="text-center relative z-10">
                        <h1 class="text-white text-3xl font-black mb-1 tracking-tight">{{ $title }}</h1>
                        <p class="text-white/70 text-sm">{{ $subtitle }}</p>
                    </div>
                </div>

                <div class="bg-white -mt-16 rounded-tr-[3rem] relative z-10 px-8 pt-12 pb-10">
                    @livewire('voucher-portal', ['slug' => $slug ?? request()->route('slug')] + request()->only(['link-login', 'mac', 'ip', 'router']))
                </div>
            </div>

            <div class="text-center mt-6 text-gray-500">
                <p class="text-xs flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-wifi" style="color: {{ $color }}"></i>
                    Powered by <span class="font-semibold text-gray-600">HiFastLink</span>
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
