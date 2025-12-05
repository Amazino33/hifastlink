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
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="flex-grow">
            {{ $slot }}
        </main>

        <!-- Modern Footer -->
        <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white relative overflow-hidden mt-auto">
            <!-- Animated background elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            </div>

            <div class="relative mx-auto w-full max-w-screen-xl p-6 py-12 lg:py-16">
                <!-- Main content grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                    
                    <!-- Brand Section -->
                    <div class="lg:col-span-1">
                        <a href="/" class="flex items-center mb-6 group">
                            <div class="relative">
                                <img src="https://flowbite.com/docs/images/logo.svg" class="h-10 me-3 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-12 filter brightness-0 invert" alt="HiFastLink Logo" />
                            </div>
                            <span class="text-white text-2xl font-black">HiFastLink</span>
                        </a>
                        <p class="text-gray-300 leading-relaxed mb-6">
                            Lightning-fast satellite internet that connects you anywhere, anytime. Experience the future of connectivity.
                        </p>
                        <div class="flex space-x-3">
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-blue-500 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110 hover:-translate-y-1">
                                <i class="fab fa-facebook-f text-white"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-blue-400 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110 hover:-translate-y-1">
                                <i class="fab fa-twitter text-white"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-pink-500 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110 hover:-translate-y-1">
                                <i class="fab fa-instagram text-white"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-purple-500 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110 hover:-translate-y-1">
                                <i class="fab fa-discord text-white"></i>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white/10 hover:bg-gray-700 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110 hover:-translate-y-1">
                                <i class="fab fa-github text-white"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-yellow-400 font-bold text-lg mb-6 uppercase tracking-wide">Quick Links</h3>
                        <ul class="space-y-3">
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Home
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    About Us
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Services
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Pricing Plans
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Coverage Map
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h3 class="text-yellow-400 font-bold text-lg mb-6 uppercase tracking-wide">Support</h3>
                        <ul class="space-y-3">
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Help Center
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Contact Us
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    FAQs
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Installation Guide
                                </a>
                            </li>
                            <li>
                                <a href="#" class="text-gray-300 hover:text-yellow-400 transition-colors duration-300 flex items-center group">
                                    <i class="fa-solid fa-chevron-right text-xs mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                    Network Status
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h3 class="text-yellow-400 font-bold text-lg mb-6 uppercase tracking-wide">Get In Touch</h3>
                        <ul class="space-y-4">
                            <li class="flex items-start group">
                                <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-500 transition-all duration-300">
                                    <i class="fa-solid fa-location-dot text-yellow-400 group-hover:text-white"></i>
                                </div>
                                <div>
                                    <p class="text-gray-300">Lagos, Nigeria</p>
                                    <p class="text-sm text-gray-400">Serving all of Nigeria</p>
                                </div>
                            </li>
                            <li class="flex items-start group">
                                <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-500 transition-all duration-300">
                                    <i class="fa-solid fa-phone text-yellow-400 group-hover:text-white"></i>
                                </div>
                                <div>
                                    <p class="text-gray-300">+234 800 123 4567</p>
                                    <p class="text-sm text-gray-400">24/7 Support Line</p>
                                </div>
                            </li>
                            <li class="flex items-start group">
                                <div class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-500 transition-all duration-300">
                                    <i class="fa-solid fa-envelope text-yellow-400 group-hover:text-white"></i>
                                </div>
                                <div>
                                    <p class="text-gray-300">info@hifastlink.com</p>
                                    <p class="text-sm text-gray-400">We reply in 24 hours</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-white/20 mb-8"></div>

                <!-- Bottom Bar -->
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <div class="text-gray-400 text-sm text-center md:text-left">
                        © 2024 <span class="text-white font-semibold">HiFastLink</span>. All Rights Reserved. | Powered by Satellite Technology
                    </div>
                    <div class="flex flex-wrap justify-center gap-6">
                        <a href="#" class="text-gray-400 hover:text-yellow-400 text-sm transition-colors duration-300">Privacy Policy</a>
                        <span class="text-gray-600">•</span>
                        <a href="#" class="text-gray-400 hover:text-yellow-400 text-sm transition-colors duration-300">Terms of Service</a>
                        <span class="text-gray-600">•</span>
                        <a href="#" class="text-gray-400 hover:text-yellow-400 text-sm transition-colors duration-300">Cookie Policy</a>
                        <span class="text-gray-600">•</span>
                        <a href="#" class="text-gray-400 hover:text-yellow-400 text-sm transition-colors duration-300">Sitemap</a>
                    </div>
                </div>

                <!-- Newsletter Section (Optional) -->
                <div class="mt-12 p-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-2xl">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="text-center md:text-left">
                            <h4 class="text-2xl font-bold text-white mb-2">Stay Connected!</h4>
                            <p class="text-blue-100">Get the latest updates on new plans and exclusive offers.</p>
                        </div>
                        <div class="flex w-full md:w-auto">
                            <input type="email" placeholder="Enter your email" class="px-6 py-3 rounded-l-full w-full md:w-80 focus:outline-none focus:ring-2 focus:ring-yellow-400 text-gray-900">
                            <button class="bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-bold px-8 py-3 rounded-r-full transition-all duration-300 transform hover:scale-105 whitespace-nowrap">
                                Subscribe
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>