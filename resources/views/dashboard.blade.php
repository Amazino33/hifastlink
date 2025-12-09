<x-app-layout>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="space-y-6">
                <!-- Welcome Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-3xl p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                                Hi, {{ Auth::user()->name }} 👋
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400">Welcome back to your dashboard</p>
                        </div>
                        <div class="hidden md:flex items-center space-x-3">
                            <button class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                                <i class="fa-solid fa-bell text-gray-600 dark:text-gray-300 text-xl"></i>
                            </button>
                            <button class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                                <i class="fa-solid fa-gear text-gray-600 dark:text-gray-300 text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Left Column - Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Subscription Status Card -->
                        <div class="bg-gradient-to-r from-primary to-blue-300 rounded-3xl p-8 shadow-2xl relative overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                            <!-- Animated background -->
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-blue-100 text-sm font-semibold uppercase tracking-wide">Your Subscription</span>
                                    <span class="bg-green-400 text-gray-900 px-4 py-1 rounded-full text-xs font-bold">CONNECTED</span>
                                </div>
                                <div class="mb-6">
                                    <div class="text-6xl font-black text-white mb-2">14 Days</div>
                                    <div class="text-blue-100 text-lg">Unlimited connection</div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <button class="bg-white hover:bg-yellow-400 text-gray-900 font-bold px-6 py-3 rounded-full transition-all duration-300 transform hover:scale-105">
                                        Renew Plan
                                    </button>
                                    <button class="text-white hover:text-yellow-300 font-semibold transition-colors duration-300">
                                        View Details →
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Usage Card -->
                        <div class="bg-gradient-to-br from-primary via-blue-500 to-secondary rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                            <!-- Animated background -->
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <div class="text-blue-100 text-sm font-semibold uppercase tracking-wide mb-2">Data Activity</div>
                                        <div class="text-white/80 text-xs">Valid Until: 31 Dec 23</div>
                                    </div>
                                    <button class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-4 py-2 rounded-full text-white text-sm font-semibold transition-all duration-300">
                                        Download Invoice
                                    </button>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="text-6xl font-black text-white mb-2">35.6 GB</div>
                                    <div class="text-blue-100 text-lg mb-6">Used this month</div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="relative h-4 bg-white/20 rounded-full overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-pink-500 rounded-full" style="width: 35.6%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-blue-100 mt-2">
                                        <span>35.6 GB used</span>
                                        <span>Unlimited</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hot Deals Section -->
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white">Hot Deals</h3>
                                <a href="#" class="text-blue-600 hover:text-blue-700 font-semibold text-sm">View All →</a>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <!-- Deal Card 1 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>

                                <!-- Deal Card 2 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>

                                <!-- Deal Card 3 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>

                                <!-- Deal Card 4 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Row of Deals -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                                <!-- Deal Card 5 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>

                                <!-- Deal Card 6 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>

                                <!-- Deal Card 7 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>

                                <!-- Deal Card 8 -->
                                <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                    <div class="text-center space-y-2">
                                        <div class="bg-white py-4 rounded-b-3xl">
                                            <div class="text-3xl font-black text-primary">30</div>
                                            <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                            <div class="border-t border-primary/30 pt-2 mt-2">
                                                <div class="text-2xl font-black text-primary">10GB</div>
                                                <div class="text-blue-400 text-xs">Data</div>
                                            </div>
                                        </div>   
                                        <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                            ₦5,000
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Check More Offers Button -->
                            <button class="w-full mt-6 bg-gradient-to-r from-primary to-secondary hover:from-blue-700 hover:to-blue-700 text-white font-bold py-4 rounded-3xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 group">
                                <span class="flex items-center justify-center">
                                    CHECK MORE OFFERS
                                    <i class="fa-solid fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Right Column - Quick Actions & Stats -->
                    <div class="space-y-6">
                        <!-- Search Bar -->
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    placeholder="Search..." 
                                    class="w-full pl-12 pr-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 transition-all duration-300"
                                >
                                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6">Quick Stats</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-gray-700 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-signal text-white"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">Connection</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Status</div>
                                        </div>
                                    </div>
                                    <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold">Active</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-gray-700 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-gauge-high text-white"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">Speed</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Current</div>
                                        </div>
                                    </div>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">80 Mbps</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-gray-700 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-clock text-white"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">Uptime</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">This Month</div>
                                        </div>
                                    </div>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">99.9%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Support Card -->
                        <div class="bg-gradient-to-br from-secondary to-nav rounded-3xl p-6 shadow-xl">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-white/30 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fa-solid fa-headset text-white text-3xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-white mb-2">Need Help?</h4>
                                <p class="text-white/80 text-sm mb-4">Our support team is available 24/7</p>
                                <button class="bg-white hover:bg-gray-100 text-gray-900 font-bold px-6 py-3 rounded-full transition-all duration-300 transform hover:scale-105">
                                    Contact Support
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>