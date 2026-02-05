<x-app-layout>
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl lg:text-6xl font-black text-white mb-4">
                    Installation <span class="text-blue-300">Guide</span>
                </h1>
                <p class="text-xl text-white/90 max-w-2xl mx-auto">
                    Get connected in minutes with our simple setup process.
                </p>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-4xl mx-auto">
            {{-- Quick Start Steps --}}
            <div class="mb-16">
                <h2 class="text-3xl font-black text-gray-900 mb-8 text-center">Quick Start in 3 Easy Steps</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl font-black">
                            1
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Subscribe</h3>
                        <p class="text-gray-600">Choose a plan and complete payment to activate your account</p>
                    </div>

                    <div class="text-center">
                        <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl font-black">
                            2
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Connect</h3>
                        <p class="text-gray-600">Find the HiFastLink Wi-Fi network and select it on your device</p>
                    </div>

                    <div class="text-center">
                        <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl font-black">
                            3
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Login</h3>
                        <p class="text-gray-600">Use your credentials from the dashboard to authenticate and start browsing</p>
                    </div>
                </div>
            </div>

            {{-- Detailed Instructions --}}
            <div class="space-y-8">
                <h2 class="text-3xl font-black text-gray-900 mb-8">Detailed Instructions</h2>

                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mr-4">
                            <i class="fa-solid fa-mobile-screen text-primary text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">For Mobile Devices</h3>
                            <ol class="space-y-3 text-gray-600 list-decimal list-inside">
                                <li>Open Wi-Fi settings on your smartphone or tablet</li>
                                <li>Look for "HiFastLink" or "HiFastLink-Public" in available networks</li>
                                <li>Tap to connect to the network</li>
                                <li>A login page will appear automatically (captive portal)</li>
                                <li>Enter your username and RADIUS password from your dashboard</li>
                                <li>Click "Connect" and you're all set!</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mr-4">
                            <i class="fa-solid fa-laptop text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">For Laptops & Computers</h3>
                            <ol class="space-y-3 text-gray-600 list-decimal list-inside">
                                <li>Click the Wi-Fi icon in your system tray or menu bar</li>
                                <li>Select "HiFastLink" from available networks</li>
                                <li>Click "Connect"</li>
                                <li>Open your web browser - the login page should appear automatically</li>
                                <li>If the page doesn't appear, navigate to <code class="bg-gray-100 px-2 py-1 rounded">http://192.168.1.1</code></li>
                                <li>Enter your credentials and click "Login"</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-start mb-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mr-4">
                            <i class="fa-solid fa-satellite-dish text-purple-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">Satellite Installation</h3>
                            <p class="text-gray-600 mb-4">For satellite plans, professional installation is required. Our technicians will:</p>
                            <ol class="space-y-3 text-gray-600 list-decimal list-inside">
                                <li>Survey your location for optimal satellite positioning</li>
                                <li>Install and align the satellite dish</li>
                                <li>Set up the modem and router</li>
                                <li>Configure your devices and test the connection</li>
                                <li>Provide training on using your dashboard</li>
                            </ol>
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-800"><strong>Note:</strong> Installation is scheduled within 24-48 hours of payment and is completely free of charge.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Troubleshooting --}}
            <div class="mt-16">
                <h2 class="text-3xl font-black text-gray-900 mb-8">Troubleshooting</h2>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded-lg">
                    <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
                        <i class="fa-solid fa-triangle-exclamation text-yellow-600 mr-2"></i>
                        Common Issues
                    </h3>
                    <div class="space-y-4 text-gray-700">
                        <div>
                            <p class="font-semibold">Login page not appearing?</p>
                            <p class="text-sm">Try typing <code class="bg-white px-2 py-1 rounded">http://192.168.1.1</code> directly in your browser address bar.</p>
                        </div>
                        <div>
                            <p class="font-semibold">Invalid credentials error?</p>
                            <p class="text-sm">Double-check your username and password from your dashboard. Credentials are case-sensitive.</p>
                        </div>
                        <div>
                            <p class="font-semibold">Can't see the network?</p>
                            <p class="text-sm">Make sure Wi-Fi is enabled on your device and you're within range of the access point. Try refreshing the network list.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Need Help CTA --}}
            <div class="mt-16 bg-gradient-to-br from-primary to-secondary rounded-3xl p-12 text-center text-white">
                <h2 class="text-3xl font-black mb-4">Need Installation Help?</h2>
                <p class="text-xl text-white/90 mb-8">Our support team is ready to assist you 24/7</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('contact') }}" class="bg-white text-primary font-bold px-8 py-4 rounded-full hover:bg-blue-50 transition-all duration-300 transform hover:scale-105">
                        Contact Support
                    </a>
                    <a href="{{ route('help') }}" class="bg-white/20 border-2 border-white text-white font-bold px-8 py-4 rounded-full hover:bg-white/30 transition-all duration-300 transform hover:scale-105">
                        Help Center
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
