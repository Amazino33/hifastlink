<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Maintenance Mode</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: '#007AFE',
                        secondary: '#004D9F',
                        background: '#097fff',
                        nav: '#004d9f',
                    },
                    screens: {
                        'xs': '475px',
                    },
                },
            },
        }
    </script>

    <style>
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .animate-pulse-slow {
            animation: pulse-slow 4s infinite ease-in-out;
        }

        .animate-float {
            animation: float 3s infinite ease-in-out;
        }

        .maintenance-icon {
            filter: drop-shadow(0 10px 25px rgba(0, 122, 254, 0.3));
        }
    </style>
</head>

<body class="font-sans antialiased bg-gradient-to-br from-blue-50 via-white to-blue-50 min-h-screen flex items-center justify-center overflow-auto">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 opacity-20 pointer-events-none">
        <div class="absolute top-20 left-20 w-96 h-96 bg-primary rounded-full blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-20 right-20 w-80 h-80 bg-secondary rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-300 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    <!-- Maintenance Card -->
    <div class="relative z-10 w-full max-w-lg mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary to-secondary p-8 text-center text-white relative overflow-hidden">
                <!-- Decorative elements -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full blur-2xl"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white rounded-full blur-2xl"></div>
                </div>

                <div class="relative z-10">
                    <!-- Maintenance Icon -->
                    <div class="inline-block p-4 bg-white/20 rounded-2xl mb-6 animate-float">
                        <i class="fas fa-tools text-4xl text-white maintenance-icon"></i>
                    </div>

                    <h1 class="text-3xl font-black mb-2">Under Maintenance</h1>
                    <p class="text-blue-100 text-sm">We're working hard to improve your experience</p>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8 text-center">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-3">HiFastLink is Currently Down</h2>
                    <p class="text-gray-600 leading-relaxed">
                        We're performing scheduled maintenance to bring you an even better internet experience.
                        Our team is working diligently to get everything back online as soon as possible.
                    </p>
                </div>

                <!-- Status Indicators -->
                <div class="grid grid-cols-3 gap-4 mb-8">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-clock text-orange-600 text-lg"></i>
                        </div>
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Estimated</p>
                        <p class="text-sm font-bold text-gray-800">2-4 Hours</p>
                    </div>

                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-users-cog text-blue-600 text-lg"></i>
                        </div>
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Team</p>
                        <p class="text-sm font-bold text-gray-800">Working</p>
                    </div>

                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-rocket text-green-600 text-lg"></i>
                        </div>
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Goal</p>
                        <p class="text-sm font-bold text-gray-800">Better Service</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-xs text-gray-500 mb-2">
                        <span>Maintenance Progress</span>
                        <span>75%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-primary to-secondary h-2 rounded-full animate-pulse" style="width: 75%"></div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="border-t border-gray-200 pt-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Need immediate assistance? Contact our support team.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="mailto:support@hifastlink.com"
                           class="inline-flex items-center justify-center px-6 py-3 bg-primary hover:bg-secondary text-white font-bold rounded-full transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <i class="fas fa-envelope mr-2"></i>
                            Email Support
                        </a>

                        <a href="tel:+2341234567890"
                           class="inline-flex items-center justify-center px-6 py-3 bg-white border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold rounded-full transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-phone mr-2"></i>
                            Call Support
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 text-center">
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} HiFastLink. Powered by Satellite Technology
                </p>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 mb-2">We'll be back soon!</p>
            <div class="flex justify-center space-x-1">
                <div class="w-2 h-2 bg-primary rounded-full animate-pulse"></div>
                <div class="w-2 h-2 bg-primary rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                <div class="w-2 h-2 bg-primary rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
            </div>
        </div>
    </div>

    <!-- Refresh Script (optional) -->
    <script>
        // Auto refresh every 5 minutes to check if maintenance is over
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>