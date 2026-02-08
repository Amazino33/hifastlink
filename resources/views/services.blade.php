<x-app-layout>
    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-primary to-secondary py-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-1/4 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-blue-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl lg:text-6xl font-black text-white mb-6">
                Our <span class="text-blue-300">Services</span>
            </h1>
            <p class="text-xl text-white/90 max-w-3xl mx-auto">
                Comprehensive connectivity solutions designed for Nigerian environments. From individual users to large organizations, we deliver reliable internet that works.
            </p>
        </div>
    </section>

    {{-- Core Services Section --}}
    <section class="py-20 px-6 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-black text-gray-900 mb-4">
                    What We <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">Offer</span>
                </h2>
                <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                {{-- Service 1 --}}
                <div class="bg-gradient-to-br from-blue-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-2 border-blue-100">
                    <div class="bg-primary w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-satellite text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Satellite Internet</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        High-speed satellite connectivity up to 80 Mbps. Perfect for remote locations where traditional infrastructure fails. Zero dependency on terrestrial networks.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>99.9% uptime guarantee</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Works in any weather</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Portable equipment</span>
                        </li>
                    </ul>
                </div>

                {{-- Service 2 --}}
                <div class="bg-gradient-to-br from-green-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-2 border-green-100">
                    <div class="bg-green-600 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-wifi text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Wireless Broadband</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Point-to-point and point-to-multipoint wireless solutions for homes, offices, and camps. Rapid deployment with professional installation.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Installation in 24 hours</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Scalable bandwidth</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>24/7 monitoring</span>
                        </li>
                    </ul>
                </div>

                {{-- Service 3 --}}
                <div class="bg-gradient-to-br from-purple-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-2 border-purple-100">
                    <div class="bg-purple-600 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-network-wired text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Network Infrastructure</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Complete network design, installation, and management. From single routers to enterprise-grade infrastructure for military bases and corporations.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Custom network design</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Security hardening</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>On-site support</span>
                        </li>
                    </ul>
                </div>

                {{-- Service 4 --}}
                <div class="bg-gradient-to-br from-orange-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-2 border-orange-100">
                    <div class="bg-orange-600 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-users text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Family Plans</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Shared data pools for families and small teams. One account, multiple users. Perfect for households.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Centralized billing</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Usage monitoring</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Parental controls</span>
                        </li>
                    </ul>
                </div>

                {{-- Service 5 --}}
                <div class="bg-gradient-to-br from-red-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-2 border-red-100">
                    <div class="bg-red-600 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-headset text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">IT Support & Maintenance</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Proactive network monitoring and maintenance. Our local team prevents downtime before it happens. Remote and on-site support available.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>24/7 helpdesk</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Regular health checks</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Equipment replacement</span>
                        </li>
                    </ul>
                </div>

                {{-- Service 6 --}}
                <div class="bg-gradient-to-br from-indigo-50 to-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-2 border-indigo-100">
                    <div class="bg-indigo-600 w-16 h-16 rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-shield-halved text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Secure Communications</h3>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Encrypted connections for sensitive operations. VPN setup, firewall configuration, and intrusion detection for mission-critical networks.
                    </p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Industry-grade encryption</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Access control</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <i class="fa-solid fa-check text-green-500"></i>
                            <span>Threat monitoring</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Industries We Serve --}}
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-black text-gray-900 mb-4">
                    Industries We <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">Serve</span>
                </h2>
                <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 text-center">
                    <i class="fa-solid fa-building text-4xl text-primary mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Government & Defense</h3>
                    <p class="text-gray-600 text-sm">Secure connectivity for operations and administration</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 text-center">
                    <i class="fa-solid fa-oil-well text-4xl text-primary mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Oil & Gas</h3>
                    <p class="text-gray-600 text-sm">Remote site connectivity and data transmission</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 text-center">
                    <i class="fa-solid fa-graduation-cap text-4xl text-primary mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Education</h3>
                    <p class="text-gray-600 text-sm">Campus-wide networks and e-learning infrastructure</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 text-center">
                    <i class="fa-solid fa-house text-4xl text-primary mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Residential</h3>
                    <p class="text-gray-600 text-sm">Home internet for families and individuals</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Call to Action --}}
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl lg:text-5xl font-black text-white mb-6">
                Ready to Get Connected?
            </h2>
            <p class="text-xl text-white/90 mb-8">
                Contact us today for a custom solution tailored to your needs
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('contact') }}" class="bg-white text-primary font-bold text-lg py-4 px-8 rounded-full hover:bg-gray-100 transform hover:scale-105 transition-all duration-300 shadow-xl">
                    Contact Us
                </a>
                <a href="{{ route('pricing') }}" class="bg-blue-400 text-white font-bold text-lg py-4 px-8 rounded-full hover:bg-blue-500 transform hover:scale-105 transition-all duration-300 shadow-xl">
                    View Pricing
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
