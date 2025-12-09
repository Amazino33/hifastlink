<x-app-layout>
    {{-- Hero Section --}}
    <section class="relative bg-white overflow-hidden pt-20">
        <!-- Animated background elements -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-1/4 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-pink-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>

        <div class="relative max-w-7xl mx-auto">
            <div class="text-black space-y-6 animate-fade-in">
                <div class="inline-block text-4xl font-thin uppercase tracking-widest px-6 lg:px-8 hidden lg:block">
                    Access The Internet
                </div>
                <div class="inline-block text-2xl font-thin uppercase tracking-widest px-6 lg:px-8 lg:hidden">
                    Access The Internet
                </div>
                <h1 class="!-mt-1 text-5xl lg:text-7xl font-black px-6 lg:px-8">
                    ANYWHERE<br>
                    ANYTIME
                </h1>
                <p class="!-mt-1 text-sm font-thin px-6 lg:px-8">Fast, Reliable, Secure & Affordable</p>
                
                <!-- Speed Card -->
                <div class="bg-primary border border-white/20 rounded-2xl p-8 transform hover:scale-105 transition-all duration-300 shadow-2xl text-white">
                    <div class="flex items-end gap-3 mb-1">
                        <h2 class="text-6xl font-black">80 Mbps</h2>
                    </div>
                    <div class="text-lg font-bold uppercase mb-1">Unbeatable Speed</div>
                    <p class="text-sm leading-relaxed mb-6 lg:hidden">
                        Get consistent speeds up to 80 Mbps, engineered to work powerfully even in remote areas where other networks fail. Internet that truly works.
                    </p>
                    <p class="text-sm leading-relaxed mb-6 lg:block hidden">
                        HiFastLink delivers a stable, powerful connection engineered for remote Nigerian<br>environments. Our satellite service ensures you receive consistent speeds up to<br>80 Mbps—even when others fail. Finally, internet that truly works.
                    </p>
                    <button class="w-fit bg-secondary hover:bg-blue-800 font-bold py-4 px-8 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl text-white">
                        Get Started Now →
                    </button>
                </div>
            </div>

            <div class="hidden lg:block absolute top-0 right-0">
                <img src="{{ asset('images/hero.png') }}" alt="Satellite Dish" class="w-[34rem] h-[34rem] object-contain">
            </div>
        </div>
    </section>

    {{-- Why Choose Us Section --}}
    <section class="py-20 px-6 bg-gray-50 relative overflow-hidden">
        
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-5xl lg:text-6xl font-black text-gray-900 mb-4">
                    Why Choose <span class="text-transparent bg-clip-text bg-primary">Us?</span>
                </h2>
                <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="bg-primary w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
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
                <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="bg-primary w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
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
                <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 flex flex-col items-center text-center">
                    <div class="bg-primary w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
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
                Stream <span class="text-transparent bg-clip-text bg-primary">Everything</span> Without Limits
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
            <div class="bg-blue-50 p-12 rounded-3xl shadow-xl border-2 border-blue-100 relative overflow-hidden flex flex-col items-center space-y-8">
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-200 rounded-full blur-3xl opacity-30 -mr-32 -mt-32"></div>
                <div class="relative z-10">
                    <h2 class="text-4xl font-black text-gray-900 mb-6 text-center">
                        About <span class="text-transparent bg-clip-text bg-primary">HiFastLink</span>
                    </h2>
                    <p class="text-lg text-gray-700 leading-relaxed text-center">
                        We are redefining connectivity in Nigeria. HiFastLink delivers truly unlimited, ultra-fast internet designed for heavy users—without the heavy price tag. Whether you are in Lagos, Abuja, Uyo, or remote locations like Borno, our network guarantees speed, stability, and zero buffering. Experience internet without limits.
                    </p>
                </div>
                
                <button class="w-fit bg-secondary hover:bg-blue-800 font-bold py-4 px-8 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl text-white">
                    Get Started Now →
                </button>
            </div>
        </div>
    </section>

    {{-- Our Mission Section --}}
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-5xl lg:text-6xl font-black text-gray-900 mb-4">
                    Our <span class="text-transparent bg-clip-text bg-primary">Mission</span>
                </h2>
                <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Mission Card 1 -->
                <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-blue-100 mb-6 leading-relaxed">
                        Plug-and-play satellite systems that establish high-speed command posts in minutes, independent of local infrastructure.
                    </p>
                    <h3 class="text-2xl font-bold">
                        Rapid Response Connectivity
                    </h3>
                </div>

                <!-- Mission Card 2 -->
                <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-purple-100 mb-6 leading-relaxed">
                        Guaranteed up to 80 Mbps bandwidth for secure communications, real-time intelligence, and logistics support in dead zones.
                    </p>
                    <h3 class="text-2xl font-bold">
                        Mission-Critical Backhaul
                    </h3>
                </div>

                <!-- Mission Card 3 -->
                <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-pink-100 mb-6 leading-relaxed">
                        We don't just install; we manage. Our local team monitors your network health to prevent downtime before it happens.
                    </p>
                    <h3 class="text-2xl font-bold">
                        IT Support & Maintenance
                    </h3>
                </div>

                <!-- Mission Card 4 -->
                <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 text-white">
                    <p class="text-indigo-100 mb-6 leading-relaxed">
                        Managed Wi-Fi solutions for barracks that allow personnel to stay connected with family without compromising operational bandwidth.
                    </p>
                    <h3 class="text-2xl font-bold">
                        Camp Morale Networks
                    </h3>
                </div>
            </div>
        </div>
    </section>

    {{-- Call to Action Section --}}
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary relative overflow-hidden">
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