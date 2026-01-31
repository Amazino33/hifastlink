<!-- Removed: dashboard moved to `resources/views/dashboard.blade.php` -->
<div wire:poll.10s>
                <!-- Welcome Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-3xl p-8 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                                Hi, {{ $user->name }} 👋
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400">Welcome back to your dashboard</p>
                            @if($connectionStatus === 'active')
                                <div class="flex items-center space-x-3">
                                    <span class="relative flex h-3 w-3">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500 ring-2 ring-green-300"></span>
                                    </span>
                                    <p class="text-sm font-semibold">
                                        <span class="text-green-600 dark:text-green-400">Online now</span>
                                        <span class="ml-2 {{ $currentIp === 'Offline' ? 'text-gray-500 dark:text-gray-400' : 'text-green-600 dark:text-green-400' }}">IP: {{ $currentIp }}</span>
                                    </p>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-500">Currently offline</p>
                            @endif
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

                {{-- Livewire toast fallback element (rendered when session has a toast) --}}
                @if (session('toast_message'))
                    <div id="livewire-toast" data-toast="{{ session('toast_message') }}" style="display:none"></div>
                @endif

                <!-- Main Grid -->
                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Left Column - Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Subscription Status Card -->
                        <div class="bg-gradient-to-r from-primary to-blue-300 rounded-3xl p-8 shadow-2xl relative overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-blue-100 text-sm font-semibold uppercase tracking-wide">Your Subscription</span>

                                    <div class="flex items-center">
                                        <span class="relative inline-flex items-center px-4 py-1 rounded-full text-xs font-bold {{ $connectionStatus === 'active' ? 'bg-green-500 text-white' : 'bg-gray-600 text-white' }}">
                                            @if($connectionStatus === 'active')
                                                <span class="relative inline-flex mr-2">
                                                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-50"></span>
                                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                                                </span>
                                                ONLINE
                                            @else
                                                OFFLINE
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    @if($subscriptionStatus === 'active')
                                        <div class="text-6xl font-black text-white mb-2">{{ $subscriptionDays }}</div>
                                        <div class="text-sm text-white/80 font-semibold mb-3">{{ $subscriptionDays === 1 ? 'day remaining' : 'days remaining' }}</div>
                                        <div class="text-blue-100 text-lg">{{ $formattedDataLimit }} connection</div>
                                    @else
                                        <div class="text-6xl font-black text-white mb-2">Expired</div>
                                        <div class="text-blue-100 text-lg">Please renew your subscription</div>
                                    @endif
                                    
                                    @if($connectionStatus === 'active')
                                        <div class="mt-4 space-y-2">
                                            <div class="flex items-center text-sm">
                                                <i class="fa-solid fa-network-wired mr-2"></i>
                                                <span class="{{ $currentIp === 'Offline' ? 'text-gray-400 dark:text-gray-400' : 'text-blue-100' }}">IP: {{ $currentIp }}</span>
                                            </div>
                                            <div class="flex items-center text-blue-100 text-sm">
                                                <i class="fa-solid fa-clock mr-2"></i>
                                                <span>Uptime: {{ $uptime }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-4">
                                    <button class="bg-white hover:bg-yellow-400 text-gray-900 font-bold px-6 py-3 rounded-full transition-all duration-300 transform hover:scale-105">
                                        {{ $subscriptionStatus === 'active' ? 'Renew Plan' : 'Subscribe Now' }}
                                    </button>
                                    <button class="text-white hover:text-yellow-300 font-semibold transition-colors duration-300">
                                        View Details →
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Data Usage Card -->
                        <div class="bg-gradient-to-br from-primary via-blue-500 to-secondary rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                            <div class="absolute inset-0 opacity-20">
                                <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
                            </div>
                            
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <div class="text-blue-100 text-sm font-semibold uppercase tracking-wide mb-2">
                                            {{ $connectionStatus === 'active' ? 'Live Data Usage' : 'Data Usage' }}
                                        </div>
                                        <div class="text-white/80 text-xs">Valid Until: {{ $validUntil }} ({{ $planValidityHuman }})</div>
                                    </div>
                                    <button class="bg-white/20 hover:bg-white/30 backdrop-blur-sm px-4 py-2 rounded-full text-white text-sm font-semibold transition-all duration-300">
                                        Download Invoice
                                    </button>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="text-6xl font-black text-white mb-2">{{ $formattedTotalUsed }}</div>
                                    <div class="text-blue-100 text-lg mb-6">{{ $connectionStatus === 'active' ? 'Current session' : 'Total used' }}</div>
                                    
                                    <!-- Progress Bar -->
                                    @if($formattedDataLimit !== 'Unlimited')
                                        @php
                                            $pct = min(100, $dataUsagePercentage);
                                            $barGradient = $pct < 70 ? 'from-green-400 to-green-600' : ($pct < 90 ? 'from-yellow-400 to-pink-500' : 'from-red-500 to-red-700');
                                        @endphp

                                        <div class="flex items-center justify-between mb-2 text-xs text-blue-100">
                                            <div class="font-semibold">{{ $formattedTotalUsed }} used</div>
                                            <div class="font-medium">{{ $pct }}%</div>
                                        </div>

                                        <div class="relative h-4 bg-white/20 rounded-full overflow-hidden">
                                            <div class="absolute inset-0 rounded-full bg-gradient-to-r {{ $barGradient }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-xs text-blue-100 mt-2">
                                            <span class="text-sm">{{ $formattedTotalUsed }} used</span>
                                            <span class="text-sm">{{ $formattedDataLimit }} total</span>
                                        </div>
                                    @else
                                        <div class="flex justify-between text-xs text-blue-100 mt-2">
                                            <span>{{ $formattedTotalUsed }} used</span>
                                            <span>Unlimited</span>
                                        </div>
                                    @endif
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
                                @foreach($plans as $plan)
                                    <div class="bg-gradient-to-br from-primary to-blue-400 rounded-3xl shadow shadow-primary hover:shadow-2xl hover:shadow-primary transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                        <div class="text-center space-y-2 p-4">
                                            <div class="bg-white py-4 rounded-b-3xl">
                                                <div class="text-3xl font-black text-primary">{{ $plan->validity_days }}</div>
                                                <div class="text-blue-400 text-xs font-semibold uppercase tracking-wide">Days</div>
                                                <div class="border-t border-primary/30 pt-2 mt-2">
                                                    <div class="text-2xl font-black text-primary">{{ \Illuminate\Support\Number::fileSize($plan->data_limit) }}</div>
                                                    <div class="text-blue-400 text-xs">Data</div>
                                                </div>
                                            </div>   
                                            <div class="text-white font-black py-2 px-3 rounded-lg text-lg mt-3">
                                                ₦{{ number_format($plan->price) }}
                                            </div>
                                            <div class="mt-3">
                                                <form action="{{ route('pay') }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                    <button type="submit" class="bg-white text-primary hover:bg-yellow-400 font-bold px-6 py-2 rounded-full">
                                                        Pay ₦{{ number_format($plan->price) }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

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
                                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold {{ $connectionStatus === 'active' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                                        @if($connectionStatus === 'active')
                                            <span class="relative inline-flex h-2 w-2">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-50"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                            </span>
                                        @endif
                                        <span>{{ ucfirst($connectionStatus) }}</span>
                                    </span>
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
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $currentSpeed }}</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-gray-700 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-clock text-white"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">Uptime</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Current Session</div>
                                        </div>
                                    </div>
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $uptime }}</span>
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
</div>