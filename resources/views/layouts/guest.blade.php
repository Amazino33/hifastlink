<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
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
                    <div class="flex justify-center mb-6 relative z-10">
                        <div
                            class="w-24 h-24 bg-white rounded-3xl shadow-2xl flex items-center justify-center transform hover:rotate-6 transition-transform duration-300 group">
                            <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg"
                                class="block h-10 w-auto fill-current text-white drop-shadow-lg"
                                style="width: 400px;height: 70px;">
                                <!-- Center the content by translating it -->
                                <g transform="translate(73, 0)">
                                    <g fill="#007AFE" fill-opacity="0.98" stroke="None">
                                        <path
                                            d="M 0.00 67.00 L 0.00 70.00 C 2.33 95.43 0.33 122.82 1.00 149.00 C 10.16 145.90 17.96 139.94 25.70 133.70 C 33.44 127.45 40.87 122.14 48.70 115.70 C 56.52 109.25 63.63 106.08 71.25 98.25 C 78.86 90.42 88.43 89.69 96.23 97.77 C 104.03 105.85 111.24 108.17 119.08 114.92 C 126.92 121.66 134.27 126.65 142.25 132.75 C 150.22 138.86 158.02 144.92 167.00 149.00 C 167.88 124.78 165.83 95.73 167.00 72.00 C 168.17 48.27 145.56 42.30 130.30 30.70 C 115.04 19.09 98.71 10.28 83.00 0.00 L 82.00 0.00 C 67.80 9.93 51.20 19.45 37.70 30.70 C 24.19 41.95 1.94 47.66 0.00 67.00 Z">
                                        </path>
                                    </g>
                                    <g fill="#BED4FE" fill-opacity="0.98" stroke="None">
                                        <path
                                            d="M 2.00 281.00 L 3.00 281.00 C 10.82 275.10 18.89 270.10 26.77 263.77 C 34.65 257.44 42.46 253.22 50.25 246.25 C 58.04 239.27 65.00 237.24 72.77 228.77 C 80.54 220.30 89.47 222.05 97.30 229.70 C 105.14 237.34 112.57 239.96 120.30 246.70 C 128.03 253.43 136.01 257.96 143.77 264.23 C 151.53 270.50 159.45 275.50 168.00 280.00 C 168.16 266.28 167.79 250.51 168.00 237.00 C 168.21 223.49 168.03 208.55 167.00 196.00 C 165.96 183.46 156.95 179.44 148.25 172.75 C 139.54 166.06 131.38 162.05 122.70 155.30 C 114.02 148.55 105.45 146.89 96.75 139.25 C 88.04 131.62 78.42 132.37 70.08 140.08 C 61.74 147.80 53.42 150.15 45.23 157.23 C 37.04 164.31 28.07 168.19 19.75 174.75 C 11.42 181.31 1.48 185.42 1.00 198.00 C 0.52 210.59 1.26 226.81 1.00 240.00 C 0.74 253.19 0.88 268.00 2.00 281.00 Z">
                                        </path>
                                    </g>
                                </g>
                            </svg>
                        </div>
                    </div>

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

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</body>

</html>