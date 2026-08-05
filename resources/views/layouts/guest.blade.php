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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    {{-- Alpine is started here (not in app.js) to avoid conflicting with Filament's CDN Alpine on admin pages --}}
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    @livewireStyles
</head>

<body class="font-sans antialiased">
    <!-- Animated Background -->
    <div
        class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Animated background circles -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-200 rounded-full blur-3xl opacity-30 animate-pulse">
            </div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-200 rounded-full blur-3xl opacity-30 animate-pulse"
                style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 w-96 h-96 bg-pink-200 rounded-full blur-3xl opacity-20 animate-pulse"
                style="animation-delay: 2s;"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <div
                class="bg-white rounded-3xl shadow-2xl overflow-hidden transform hover:scale-[1.01] transition-transform duration-300">

                <!-- Gradient Header with Logo -->
                <div
                    class="bg-background px-8 pt-12 pb-24 relative overflow-hidden" style="background-image: url('{{ asset('images/background.png') }}'); background-size: cover; background-position: center; background-blend-mode: multiply;">
                    <!-- Animated pattern background -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 w-full h-full">
                            <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full blur-2xl animate-pulse">
                            </div>
                            <div class="absolute bottom-10 right-10 w-40 h-40 bg-yellow-300 rounded-full blur-2xl animate-pulse"
                                style="animation-delay: 0.5s;"></div>
                        </div>
                    </div>

                    <!-- Logo/Icon -->
                    <a href="{{ route('home') }}" class="flex justify-center mb-6 relative z-10">
                        <div
                            class="w-24 h-24 bg-white rounded-3xl shadow-2xl flex items-center justify-center transform hover:rotate-6 transition-transform duration-300 group p-3">
                            <img src="/logo.png" alt="HiFastLink" class="w-full h-full object-contain">
                        </div>
                    </a>

                    <!-- Brand Name -->
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

            <!-- Footer Text -->
            <div class="text-center mt-8 text-gray-600">
                <p class="text-sm">© 2024 HiFastLink. Powered by Satellite Technology</p>
            </div>
        </div>
    </div>

    @livewireScripts

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</body>

</html>