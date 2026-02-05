<x-app-layout>
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl lg:text-6xl font-black text-white mb-4">
                    Network <span class="text-blue-300">Status</span>
                </h1>
                <p class="text-xl text-white/90 max-w-2xl mx-auto">
                    Check the current status of our services and network performance.
                </p>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-6xl mx-auto">
            {{-- Overall Status --}}
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-3xl p-8 text-white text-center mb-12 shadow-xl">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center animate-pulse">
                        <i class="fa-solid fa-check text-green-600 text-3xl"></i>
                    </div>
                </div>
                <h2 class="text-3xl font-black mb-2">All Systems Operational</h2>
                <p class="text-green-100 text-lg">All services are running smoothly with no reported issues</p>
                <p class="text-sm text-green-200 mt-4">Last updated: {{ now()->format('F j, Y g:i A') }}</p>
            </div>

            {{-- Service Status Grid --}}
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Network Connectivity</h3>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">Status: <span class="font-semibold text-green-600">Operational</span></div>
                    <div class="text-sm text-gray-600">Uptime: <span class="font-semibold">99.9%</span></div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">RADIUS Authentication</h3>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">Status: <span class="font-semibold text-green-600">Operational</span></div>
                    <div class="text-sm text-gray-600">Response Time: <span class="font-semibold">&lt; 100ms</span></div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Payment Gateway</h3>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">Status: <span class="font-semibold text-green-600">Operational</span></div>
                    <div class="text-sm text-gray-600">Processing: <span class="font-semibold">Normal</span></div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">User Dashboard</h3>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">Status: <span class="font-semibold text-green-600">Operational</span></div>
                    <div class="text-sm text-gray-600">Load Time: <span class="font-semibold">&lt; 2s</span></div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Satellite Links</h3>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">Status: <span class="font-semibold text-green-600">Operational</span></div>
                    <div class="text-sm text-gray-600">Signal: <span class="font-semibold">Strong</span></div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Email Services</h3>
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">Status: <span class="font-semibold text-green-600">Operational</span></div>
                    <div class="text-sm text-gray-600">Delivery: <span class="font-semibold">Normal</span></div>
                </div>
            </div>

            {{-- Network Performance --}}
            <div class="bg-white rounded-2xl p-8 shadow-lg mb-12">
                <h2 class="text-2xl font-black text-gray-900 mb-6">Network Performance (Last 24 Hours)</h2>
                <div class="grid md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-4xl font-black text-primary mb-2">99.9%</div>
                        <div class="text-sm text-gray-600">Network Uptime</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-green-600 mb-2">45ms</div>
                        <div class="text-sm text-gray-600">Average Latency</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-blue-600 mb-2">2,847</div>
                        <div class="text-sm text-gray-600">Active Users</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-purple-600 mb-2">98%</div>
                        <div class="text-sm text-gray-600">User Satisfaction</div>
                    </div>
                </div>
            </div>

            {{-- Regional Status --}}
            <div class="bg-white rounded-2xl p-8 shadow-lg mb-12">
                <h2 class="text-2xl font-black text-gray-900 mb-6">Regional Status</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-map-marker-alt text-primary mr-3"></i>
                            <span class="font-semibold text-gray-900">Lagos</span>
                        </div>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            <i class="fa-solid fa-circle-check mr-1"></i>Operational
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-map-marker-alt text-primary mr-3"></i>
                            <span class="font-semibold text-gray-900">Abuja</span>
                        </div>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            <i class="fa-solid fa-circle-check mr-1"></i>Operational
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-map-marker-alt text-primary mr-3"></i>
                            <span class="font-semibold text-gray-900">Port Harcourt</span>
                        </div>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            <i class="fa-solid fa-circle-check mr-1"></i>Operational
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-map-marker-alt text-primary mr-3"></i>
                            <span class="font-semibold text-gray-900">Kano</span>
                        </div>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            <i class="fa-solid fa-circle-check mr-1"></i>Operational
                        </span>
                    </div>
                </div>
            </div>

            {{-- Planned Maintenance --}}
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-12">
                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                    <i class="fa-solid fa-calendar-check text-blue-600 mr-2"></i>
                    Planned Maintenance
                </h3>
                <p class="text-gray-700">No scheduled maintenance at this time. We'll notify users 48 hours in advance of any planned downtime.</p>
            </div>

            {{-- Report Issue CTA --}}
            <div class="bg-gradient-to-br from-primary to-secondary rounded-3xl p-12 text-center text-white">
                <h2 class="text-3xl font-black mb-4">Experiencing Issues?</h2>
                <p class="text-xl text-white/90 mb-8">Let us know if you're having connectivity problems</p>
                <a href="{{ route('contact') }}" class="inline-block bg-white text-primary font-bold px-8 py-4 rounded-full hover:bg-blue-50 transition-all duration-300 transform hover:scale-105">
                    Report an Issue
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
