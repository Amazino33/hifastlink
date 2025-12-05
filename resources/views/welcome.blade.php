<x-app-layout>
    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 overflow-hidden pt-20 pb-32">
        <!-- Animated background elements -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-1/4 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-pink-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="text-white space-y-6 animate-fade-in">
                    <div class="inline-block bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-semibold uppercase tracking-wide">
                        Access The Internet
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-black leading-tight">
                        ANYWHERE<br>
                        <span class="text-yellow-300">ANYTIME</span>
                    </h1>
                    <p class="text-xl text-blue-100">Fast, Reliable, Secure & Affordable</p>
                    
                    <!-- Speed Card -->
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                        <div class="flex items-end gap-3 mb-4">
                            <h2 class="text-6xl font-black text-yellow-300">80</h2>
                            <span class="text-3xl font-bold text-white mb-2">Mbps</span>
                        </div>
                        <div class="text-2xl font-bold mb-3">Unbeatable Speed</div>
                        <p class="text-blue-100 leading-relaxed mb-6">
                            Get consistent speeds up to 80 Mbps, engineered to work powerfully even in remote areas where other networks fail. Internet that truly works.
                        </p>
                        <button class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-bold py-4 px-8 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                            Get Started Now →
                        </button>
                    </div>
                </div>

                <!-- Right Visual -->
                <div class="hidden lg:block relative">
                    <div class="relative z-10">
                        <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-3xl p-8 shadow-2xl transform hover:rotate-2 transition-all duration-500">
                            <div class="aspect-square bg-gradient-to-br from-blue-400 to-purple-500 rounded-2xl flex items-center justify-center">
                                <i class="fa-solid fa-satellite text-white text-9xl animate-pulse"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Floating decorative elements -->
                    <div class="absolute -top-8 -right-8 w-32 h-32 bg-yellow-300 rounded-full blur-xl opacity-50 animate-bounce"></div>
                    <div class="absolute -bottom-8 -left-8 w-40 h-40 bg-pink-300 rounded-full blur-xl opacity-50 animate-bounce" style="animation-delay: 0.5s;"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Why Choose Us Section --}}
    <section class="py-20 px-6 bg-gray-50 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>
        
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-5xl lg:text-6xl font-black text-gray-900 mb-4">
                    Why Choose <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">Us?</span>
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-t-4 border-blue-500">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-satellite text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-blue-600 transition-colors">
                        Unshakeable Stability
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        Bypass Network Failure. Constant, Reliable Satellite Connectivity (Up to 80 Mbps). Get the secure comms and real-time data you need, anywhere.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-t-4 border-purple-500">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-wifi text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-purple-600 transition-colors">
                        Rapid Deployment
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        Portable, Rugged Setup. Non-specialist personnel can set up a high-speed command post in minutes, guaranteeing zero downtime during relocation.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 border-t-4 border-pink-500">
                    <div class="bg-gradient-to-br from-pink-500 to-pink-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-globe text-4xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4 group-hover:text-pink-600 transition-colors">
                        High-Capacity Bandwidth
                    </h3>
                    <p class="text-gray-600 leading-relaxed">
                        Provides ample bandwidth to handle large data transfers, multi-party secure video calls, and essential logistic applications without congestion.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Stream Everything Section --}}
    <section class="py-16 px-6 bg-gradient-to-br from-gray-900 to-gray-800">
        <div class="max-w-7xl mx-auto">
            <h3 class="text-4xl font-black text-center text-white mb-12">
                Stream <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-pink-500">Everything</span> Without Limits
            </h3>
            <div class="flex flex-wrap justify-center items-center gap-12">
                <i class="fab fa-youtube text-7xl text-red-500 hover:scale-125 transition-transform duration-300 cursor-pointer"></i>
                <div class="text-7xl text-red-600 font-black hover:scale-125 transition-transform duration-300 cursor-pointer">N</div>
                <i class="fab fa-spotify text-7xl text-green-500 hover:scale-125 transition-transform duration-300 cursor-pointer"></i>
                <i class="fab fa-instagram text-7xl text-pink-500 hover:scale-125 transition-transform duration-300 cursor-pointer"></i>
                <i class="fab fa-facebook text-7xl text-blue-600 hover:scale-125 transition-transform duration-300 cursor-pointer"></i>
                <i class="fab fa-twitter text-7xl text-blue-400 hover:scale-125 transition-transform duration-300 cursor-pointer"></i>
            </div>
        </div>
    </section>

    {{-- About Us Section --}}
    <section class="py-20 px-6 bg-white">
        <div class="max-w-4xl mx-auto">
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 p-12 rounded-3xl shadow-xl border-2 border-blue-100 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-200 rounded-full blur-3xl opacity-30 -mr-32 -mt-32"></div>
                <div class="relative z-10">
                    <h2 class="text-4xl font-black text-gray-900 mb-6 text-center">
                        About <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">HiFastLink</span>
                    </h2>
                    <p class="text-lg text-gray-700 leading-relaxed text-center">
                        We are redefining connectivity in Nigeria. HiFastLink delivers truly unlimited, ultra-fast internet designed for heavy users—without the heavy price tag. Whether you are in Lagos, Abuja, Uyo, or remote locations like Borno, our network guarantees speed, stability, and zero buffering. Experience internet without limits.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Our Mission Section --}}
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-5xl lg:text-6xl font-black text-gray-900 mb-4">
                    Our <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600">Mission</span>
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-pink-500 mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Mission Card 1 -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-blue-100 mb-6 leading-relaxed">
                        Plug-and-play satellite systems that establish high-speed command posts in minutes, independent of local infrastructure.
                    </p>
                    <h3 class="text-2xl font-bold text-yellow-300">
                        Rapid Response Connectivity
                    </h3>
                </div>

                <!-- Mission Card 2 -->
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-purple-100 mb-6 leading-relaxed">
                        Guaranteed up to 80 Mbps bandwidth for secure communications, real-time intelligence, and logistics support in dead zones.
                    </p>
                    <h3 class="text-2xl font-bold text-yellow-300">
                        Mission-Critical Backhaul
                    </h3>
                </div>

                <!-- Mission Card 3 -->
                <div class="bg-gradient-to-br from-pink-600 to-pink-700 p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-pink-100 mb-6 leading-relaxed">
                        We don't just install; we manage. Our local team monitors your network health to prevent downtime before it happens.
                    </p>
                    <h3 class="text-2xl font-bold text-yellow-300">
                        IT Support & Maintenance
                    </h3>
                </div>

                <!-- Mission Card 4 -->
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-indigo-100 mb-6 leading-relaxed">
                        Managed Wi-Fi solutions for barracks that allow personnel to stay connected with family without compromising operational bandwidth.
                    </p>
                    <h3 class="text-2xl font-bold text-yellow-300">
                        Camp Morale Networks
                    </h3>
                </div>
            </div>
        </div>
    </section>

    {{-- Call to Action Section --}}
    <section class="py-20 px-6 bg-gradient-to-br from-yellow-400 via-orange-400 to-red-500 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-pink-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>
        
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <h2 class="text-5xl lg:text-6xl font-black text-white mb-6">
                Don't Wait Any Longer!
            </h2>
            <p class="text-2xl text-white mb-12 font-medium">
                Get connected now, and enjoy super fast and reliable internet connection.
            </p>
            <button class="bg-white text-gray-900 font-black text-xl py-6 px-12 rounded-full hover:bg-gray-100 transform hover:scale-110 transition-all duration-300 shadow-2xl hover:shadow-3xl inline-flex items-center gap-3">
                Get Started Today
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </section>

</x-app-layout>