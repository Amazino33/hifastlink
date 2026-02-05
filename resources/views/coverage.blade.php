<x-app-layout>
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl lg:text-6xl font-black text-white mb-4">
                    Coverage <span class="text-blue-300">Map</span>
                </h1>
                <p class="text-xl text-white/90 max-w-2xl mx-auto">
                    Check if HiFastLink is available in your area. We're constantly expanding our network.
                </p>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center mb-16">
                <div>
                    <h2 class="text-4xl font-black text-gray-900 mb-6">Nationwide Coverage</h2>
                    <p class="text-gray-600 text-lg mb-6">
                        HiFastLink provides reliable internet service across Nigeria. Our network reaches urban centers, 
                        suburban areas, and expanding rural communities.
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-check text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Full Coverage</h3>
                                <p class="text-gray-600 text-sm">Lagos, Abuja, Port Harcourt, Kano, Ibadan</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-tower-cell text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Expanding Coverage</h3>
                                <p class="text-gray-600 text-sm">Kaduna, Enugu, Benin City, Calabar</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-satellite-dish text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Satellite Coverage</h3>
                                <p class="text-gray-600 text-sm">Available nationwide for remote areas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-100 rounded-2xl p-8 h-96 flex items-center justify-center">
                    <div class="text-center">
                        <i class="fa-solid fa-map-location-dot text-primary text-6xl mb-4"></i>
                        <p class="text-gray-600">Interactive map coming soon</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-primary to-secondary rounded-3xl p-12 text-center text-white">
                <h2 class="text-3xl font-black mb-4">Not Sure About Coverage?</h2>
                <p class="text-xl text-white/90 mb-8">Contact us to check availability in your specific location</p>
                <a href="{{ route('contact') }}" class="inline-block bg-white text-primary font-bold px-8 py-4 rounded-full hover:bg-blue-50 transition-all duration-300 transform hover:scale-105">
                    Contact Support
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
