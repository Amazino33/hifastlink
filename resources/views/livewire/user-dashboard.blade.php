<div class="px-4 py-6 md:px-6 lg:px-8">
<div wire:poll.10s class="mb-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-3xl p-8 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                    Hi, {{ $user->name }} 👋
                </h1>
                <p class="text-gray-600 dark:text-gray-400">Welcome back to your dashboard</p>
                @if($connectionStatus === 'active')
                    <div class="flex items-center space-x-3 mt-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500 ring-2 ring-green-300"></span>
                        </span>
                        <p class="text-sm font-semibold">
                            <span class="text-green-600 dark:text-green-400">Online now</span>
                            <span class="ml-2 {{ $currentIp === 'Offline' ? 'text-gray-500 dark:text-gray-400' : 'text-green-600 dark:text-green-400' }}">IP: {{ $currentIp }}</span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2 mt-1">
                        <i class="fa-solid fa-mobile-screen-button text-xs text-gray-500 dark:text-gray-400"></i>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Connected Devices: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $connectedDevices }}/{{ $maxDevices }}</span>
                        </p>
                    </div>
                @elseif($connectionStatus === 'unknown')
                    <div class="flex items-center space-x-3 mt-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-60"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500 ring-2 ring-blue-300"></span>
                        </span>
                        <p class="text-sm font-semibold">
                            <span class="text-blue-600 dark:text-blue-400">Connection status unknown</span>
                            <span class="ml-2 text-blue-600 dark:text-blue-400">IP: {{ $currentIp }}</span>
                        </p>
                    </div>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Unable to verify connection - RADIUS server unreachable</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Currently offline</p>
                @endif
            </div>
            <div class="hidden md:flex items-center space-x-3">
                @if(Auth::user()->is_family_admin)
                    <a href="{{ route('family') }}" class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                        <i class="fa-solid fa-users text-gray-600 dark:text-gray-300 text-xl"></i>
                    </a>
                @endif
                <button class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                    <i class="fa-solid fa-bell text-gray-600 dark:text-gray-300 text-xl"></i>
                </button>
                <button class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                    <i class="fa-solid fa-gear text-gray-600 dark:text-gray-300 text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Livewire toast fallback element --}}
    @if (session('toast_message'))
        <div id="livewire-toast" data-toast="{{ session('toast_message') }}" style="display:none"></div>
    @endif

    {{-- Success message --}}
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 mb-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-check-circle text-green-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-gradient-to-r from-blue-600 to-blue-400 rounded-3xl p-8 shadow-2xl relative overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-blue-100 text-sm font-semibold uppercase tracking-wide">Your Subscription</span>

                        <div class="flex items-center space-x-2">
                            <span id="connection-badge" class="relative inline-flex items-center px-4 py-1 rounded-full text-xs font-bold {{ $connectionStatus === 'active' ? 'bg-green-500 text-white' : 'bg-gray-600 text-white' }}">
                                <span id="online-indicator" class="relative inline-flex mr-2 {{ $connectionStatus === 'active' ? '' : 'hidden' }}">
                                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-50"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                                </span>
                                <span id="connection-text">{{ $connectionStatus === 'active' ? 'ONLINE' : 'OFFLINE' }}</span>
                            </span>

                            <!-- Connect/Disconnect Router buttons -->
                            <div id="connection-buttons">
                                <!-- Disconnect button (hidden by default, shown if device is marked as connected in localStorage) -->
                                <a href="{{ route('disconnect.bridge') }}" id="disconnect-btn" class="hidden px-3 py-1 text-xs font-semibold rounded-lg bg-red-500/80 hover:bg-red-600 text-white transition-colors focus:outline-none">
                                    <i class="fa-solid fa-power-off mr-1"></i>Disconnect
                                </a>
                                
                                <!-- Connect button (shown by default if subscription is active) -->
                                @if($subscriptionStatus === 'active')
                                    <a id="connect-to-router-btn" href="{{ route('connect.bridge') }}" target="_self" class="px-3 py-1 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors focus:outline-none">
                                        Connect to Router
                                    </a>
                                @else
                                    <a href="#hot-deals" class="px-3 py-1 text-xs font-semibold rounded-lg bg-blue-500/90 hover:bg-blue-600 text-white transition-colors">
                                        Subscribe Now
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        @if($subscriptionStatus === 'active')
                            <div class="text-6xl font-black text-white mb-2">{{ $subscriptionDays }}</div>
                            <div class="text-sm text-white/80 font-semibold mb-3">{{ $subscriptionDays === 1 ? 'day remaining' : 'days remaining' }}</div>
                            <div class="text-blue-100 text-lg">{{ $formattedDataLimit }} connection</div>
                        @elseif($subscriptionStatus === 'exhausted')
                            <div class="flex items-center space-x-3">
                                <div class="text-6xl font-black text-white mb-2">Data Exhausted</div>
                                @if($user->display_status === 'PLAN EXPIRED')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white">Plan Expired</span>
                                @endif
                            </div>
                            <div class="text-blue-100 text-lg">0 MB remaining</div>
                        @else
                            <div class="text-6xl font-black text-white mb-2">No Active Plan</div>
                            <div class="text-blue-100 text-lg">You have no active data plan. Please subscribe to use data services.</div>
                        @endif
                        
                        @if($connectionStatus === 'active')
                            <div class="mt-4 space-y-2">
                                <div class="flex items-center text-sm">
                                    <i class="fa-solid fa-network-wired mr-2 text-blue-200"></i>
                                    <span class="{{ $currentIp === 'Offline' ? 'text-gray-400' : 'text-blue-100' }}">IP: {{ $currentIp }}</span>
                                </div>
                                <div class="flex items-center text-blue-100 text-sm">
                                    <i class="fa-solid fa-clock mr-2 text-blue-200"></i>
                                    <span>Uptime: {{ $uptime }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-600 via-blue-500 to-teal-400 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <div class="text-blue-100 text-sm font-semibold uppercase tracking-wide mb-2">
                                {{ $connectionStatus === 'active' ? 'Live Data Usage' : 'Data Usage' }}
                            </div>
                            <div class="text-white text-lg font-bold mb-1">{{ $user->plan->name ?? 'No Active Plan' }}</div>
                            <div class="text-white/80 text-xs">Valid Until: {{ $validUntil }} ({{ $planValidityHuman }})</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-6xl font-black text-white mb-2">
                            @if($subscriptionStatus === 'exhausted')
                                0 MB
                            @else
                                {{ $formattedTotalUsed }}
                            @endif
                        </div>
                        <div class="text-blue-100 text-lg mb-6">{{ $subscriptionStatus === 'exhausted' ? 'Data Exhausted' : ($connectionStatus === 'active' ? 'Current session' : 'Total used') }}</div>
                        
                        @if($subscriptionStatus !== 'inactive' && $formattedDataLimit !== 'Unlimited')
                            @php
                                // Usage-based percentage: prefer the controller's family-aware percentage; fallback to the user's own accessor
                                $usedPercent = (int) ($dataUsagePercentage ?? ($user->data_usage_percentage ?? 0));
                                $pct = min(100, max(0, $usedPercent));

                                // Color logic: 0-70 Safe, 71-90 Warning, 91-100 Danger
                                $barGradient = $pct <= 70 ? 'from-green-400 to-green-600' : ($pct <= 90 ? 'from-yellow-400 via-orange-400 to-orange-600' : 'from-red-500 to-red-700');
                            @endphp

                            <div class="flex items-center justify-between mb-2 text-xs text-blue-100">
                                <div class="font-semibold">@if($subscriptionStatus === 'exhausted') 0 MB used @else {{ $formattedTotalUsed }} used @endif</div>
                                <div class="font-medium">{{ $pct }}%</div>
                            </div>

                            <div class="relative h-4 bg-white/20 rounded-full overflow-hidden">
                                <div class="absolute inset-0 rounded-full bg-gradient-to-r {{ $barGradient }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-blue-100 mt-2">
                                <span class="text-sm">@if($subscriptionStatus === 'exhausted') 0 MB used @else {{ $formattedTotalUsed }} used @endif</span>
                                <span class="text-sm">{{ $formattedDataLimit }} total</span>
                            </div>
                        @else
                            @if($subscriptionStatus === 'inactive')
                                <div class="text-sm text-blue-100 mt-2">No usage to display — subscribe to a plan to start using data.</div>
                            @else
                                <div class="flex justify-between text-xs text-blue-100 mt-2">
                                    <span>{{ $formattedTotalUsed }} used</span>
                                    <span>Unlimited</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            @if($user->pendingSubscriptions->count() > 0)
                <div class="col-span-2 mt-6">
                    <h3 class="text-gray-600 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-3">UP NEXT (Queue)</h3>

                    <div class="space-y-3">
                        @foreach($user->pendingSubscriptions as $queueItem)
                            <div class="bg-gray-100 dark:bg-gray-700/70 border border-gray-200 dark:border-gray-600 rounded-xl p-4 flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-500 flex items-center justify-center font-bold text-sm">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <h4 class="text-gray-900 dark:text-white font-bold">{{ $queueItem->plan->name }}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            Data: {{ $user->data_remaining_human }}
                                            | Duration: {{ $queueItem->plan->validity_days }} Days
                                        </p>
                                    </div>
                                </div>

                                @if($loop->first)
                                     <button wire:click="forceActivate({{ $queueItem->id }})" wire:confirm="Activate this now? Current plan will be stopped." class="text-xs bg-blue-500 text-white font-bold px-3 py-1 rounded-lg hover:bg-blue-600 transition-colors">
                                        Start Now
                                     </button>
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-500 italic">Waiting...</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-gradient-to-br from-blue-600 to-blue-400 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-ticket text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Have a Voucher?</h3>
                            <p class="text-white/60 text-xs">Enter your code to redeem data</p>
                        </div>
                    </div>

                    <form wire:submit.prevent="redeemVoucher">
                        <div class="relative">
                            <input 
                                wire:model="voucherCode" 
                                type="text" 
                                placeholder="XXXX-0000" 
                                class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/50 focus:ring-2 focus:ring-blue-300 focus:border-transparent font-mono uppercase tracking-widest text-center"
                            >
                        </div>
                        <button type="submit" class="w-full mt-3 bg-white text-blue-900 font-bold py-2 rounded-xl hover:bg-gray-100 transition-colors shadow-lg">
                            Redeem Code
                        </button>
                    </form>
                    @error('voucherCode') <span class="text-red-300 text-xs mt-2 block text-center">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="hot-deals" class="text-2xl font-black text-gray-900 dark:text-white">Hot Deals</h3>
                    <a href="{{ route('pricing') }}" class="text-sm font-bold text-primary hover:text-secondary transition-colors">
                        Show All <i class="fa-solid fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($plans->take(4) as $plan)
                        <div class="bg-gradient-to-br from-blue-600 to-blue-400 rounded-3xl shadow-lg transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                            <div class="text-center space-y-2 p-1">
                                <div class="bg-white py-4 rounded-3xl m-1">
                                    <div class="text-2xl font-black text-blue-600">{{ $plan->validity_days }}</div>
                                    <div class="text-gray-400 text-xs font-bold uppercase tracking-wide">Days</div>
                                    <div class="border-t border-gray-100 pt-2 mt-2">
                                        <div class="text-xl font-black text-gray-800">{{ $plan->data_limit_human }}</div>
                                        <div class="text-gray-400 text-xs">Data</div>
                                    </div>
                                    <div class="border-t border-gray-100 pt-2 mt-2">
                                        <div class="text-sm font-bold text-gray-600 flex items-center justify-center">
                                            <i class="fa-solid fa-devices text-blue-600 mr-1"></i>
                                            {{ $plan->max_devices ?? 1 }} {{ ($plan->max_devices ?? 1) == 1 ? 'Device' : 'Devices' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-white font-bold text-xs px-2 py-1 truncate">
                                    {{ $plan->name }}
                                </div>
                                <div class="text-white font-black py-1 px-3 text-sm">
                                    ₦{{ number_format($plan->price) }}
                                </div>
                                <div class="pb-3 px-2">
                                    <form action="{{ route('pay') }}" method="POST" class="w-full">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit" class="w-full bg-white/20 hover:bg-white/40 text-white font-bold text-xs py-2 rounded-full transition-colors">
                                            Buy
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

             <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-6">Transaction History</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-3 font-semibold">Date</th>
                                <th class="pb-3 font-semibold">Plan</th>
                                <th class="pb-3 font-semibold">Amount</th>
                                <th class="pb-3 font-semibold">Reference</th>
                                <th class="pb-3 font-semibold">Gateway</th>
                                <th class="pb-3 font-semibold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($recentTransactions as $txn)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="py-4 text-gray-600 dark:text-gray-300">{{ $txn->created_at->format('d M, h:i A') }}</td>
                                <td class="py-4 font-bold text-gray-800 dark:text-white">{{ $txn->plan ? $txn->plan->name : 'Unknown Plan' }}</td>
                                <td class="py-4 text-gray-600 dark:text-gray-300">₦{{ number_format($txn->amount) }}</td>
                                <td class="py-4 text-xs font-mono text-gray-400">{{ Str::limit($txn->reference, 12) }}</td>
                                <td class="py-4 text-gray-600 dark:text-gray-300">{{ strtoupper($txn->gateway) }}</td>
                                <td class="py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/20 dark:text-green-400">
                                        Success
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">No transactions found yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($recentTransactions->hasPages())
                    <div class="mt-6 flex justify-center">
                        {{ $recentTransactions->links() }}
                    </div>
                @endif
            </div>

        </div>

        <div class="space-y-6">
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

            <div class="bg-gradient-to-br from-indigo-900 to-blue-900 rounded-3xl p-6 shadow-xl">
                <div class="text-center">
                    <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
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

        <!-- Connect to Router Modal -->
        <div id="connect-router-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" aria-hidden="true" role="dialog" aria-labelledby="connect-router-title">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-close-modal></div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6 relative z-10">
                <h3 id="connect-router-title" class="text-lg font-bold text-gray-900 dark:text-white mb-3">Connect to Router</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Please follow the steps below before attempting to connect:</p>
                <ol class="list-decimal list-inside text-sm text-gray-700 dark:text-gray-300 space-y-2 mb-4">
                    <li>Connect to the WiFi network.</li>
                    <li>Turn off Mobile Data.</li>
                    <li>Ensure you have an active subscription.</li>
                </ol>

                <div id="connect-router-error" class="hidden text-sm text-red-500 mb-3"></div>

                <div class="flex items-center justify-end space-x-3">
                    <button data-close-modal class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">Cancel</button>
                    <button id="connect-router-confirm" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-500 transition-colors">Confirm &amp; Connect</button>
                </div>
            </div>
        </div>

        <script>
            (function(){
                // Generate unique device ID for this browser (stored permanently in localStorage)
                function getDeviceId() {
                    let deviceId = localStorage.getItem('hifastlink_device_id');
                    if (!deviceId) {
                        deviceId = 'device_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
                        localStorage.setItem('hifastlink_device_id', deviceId);
                    }
                    return deviceId;
                }
                
                // Get data from backend
                const deviceId = getDeviceId();
                const STORAGE_KEY = 'hifastlink_connected_{{ $user->id }}_' + deviceId;
                
                const connectBtn = document.getElementById('connect-to-router-btn');
                const disconnectBtn = document.getElementById('disconnect-btn');
                const connectionBadge = document.getElementById('connection-badge');
                const connectionText = document.getElementById('connection-text');
                const onlineIndicator = document.getElementById('online-indicator');
                
                // Function to update connection status display
                function updateConnectionStatus(isConnected) {
                    if (isConnected) {
                        // Show as ONLINE
                        if (connectionBadge) {
                            connectionBadge.classList.remove('bg-gray-600');
                            connectionBadge.classList.add('bg-green-500');
                        }
                        if (connectionText) connectionText.textContent = 'ONLINE';
                        if (onlineIndicator) onlineIndicator.classList.remove('hidden');
                    } else {
                        // Show as OFFLINE
                        if (connectionBadge) {
                            connectionBadge.classList.remove('bg-green-500');
                            connectionBadge.classList.add('bg-gray-600');
                        }
                        if (connectionText) connectionText.textContent = 'OFFLINE';
                        if (onlineIndicator) onlineIndicator.classList.add('hidden');
                    }
                }
                
                // Function to update button visibility based on REAL RADIUS data
                function updateButtons() {
                    // Get current RADIUS session count from Livewire data
                    const connectedDevices = {{ $connectedDevices ?? 0 }};
                    const deviceMarkedConnected = localStorage.getItem(STORAGE_KEY) === 'true';
                    
                    // LOGIC:
                    // - If NO sessions exist (connectedDevices = 0), always show "Connect"
                    // - If sessions exist AND localStorage says THIS device connected, show "Disconnect"
                    // - If sessions exist BUT localStorage says NOT connected, show "Connect" (another device)
                    
                    if (connectedDevices === 0) {
                        // No active sessions - clear localStorage and show Connect
                        localStorage.removeItem(STORAGE_KEY);
                        if (connectBtn) connectBtn.classList.remove('hidden');
                        if (disconnectBtn) disconnectBtn.classList.add('hidden');
                        updateConnectionStatus(false);
                    } else if (deviceMarkedConnected) {
                        // Sessions exist and THIS device claims to be connected - show Disconnect
                        if (connectBtn) connectBtn.classList.add('hidden');
                        if (disconnectBtn) disconnectBtn.classList.remove('hidden');
                        updateConnectionStatus(true);
                    } else {
                        // Sessions exist but THIS device not marked as connected - show Connect
                        if (connectBtn) connectBtn.classList.remove('hidden');
                        if (disconnectBtn) disconnectBtn.classList.add('hidden');
                        updateConnectionStatus(false);
                    }
                }
                
                // Initial button state
                updateButtons();
                
                // Update buttons every time Livewire refreshes (wire:poll.10s)
                document.addEventListener('livewire:load', function () {
                    Livewire.hook('message.processed', function () {
                        updateButtons();
                    });
                });
                
                // When connect button is clicked, mark this device as attempting connection
                if (connectBtn) {
                    connectBtn.addEventListener('click', function(e) {
                        localStorage.setItem(STORAGE_KEY, 'true');
                        if (disconnectBtn) disconnectBtn.classList.remove('hidden');
                        if (connectBtn) connectBtn.classList.add('hidden');
                        updateConnectionStatus(true);
                    });
                }
                
                // When disconnect button is clicked, remove the marker
                if (disconnectBtn) {
                    disconnectBtn.addEventListener('click', function() {
                        localStorage.removeItem(STORAGE_KEY);
                        updateConnectionStatus(false);
                    });
                }
                
                const openBtn = document.getElementById('connect-to-router-btn');
                const modal = document.getElementById('connect-router-modal');
                const closeBtns = modal ? modal.querySelectorAll('[data-close-modal]') : [];
                const confirmBtn = document.getElementById('connect-router-confirm');
                const errorBox = document.getElementById('connect-router-error');

                function showModal(){
                    if(!modal) return;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
                function hideModal(){
                    if(!modal) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    if(errorBox) { errorBox.classList.add('hidden'); errorBox.textContent = ''; }
                }

                openBtn && openBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    showModal();
                });

                closeBtns.forEach(btn => btn.addEventListener('click', hideModal));

                // Helper to get CSRF
                function getCsrfToken(){
                    const m = document.querySelector('meta[name="csrf-token"]');
                    return m ? m.getAttribute('content') : '';
                }

                // Timeout helper
                function promiseTimeout(promise, ms){
                    const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), ms));
                    return Promise.race([promise, timeout]);
                }

                confirmBtn && confirmBtn.addEventListener('click', async function(){
                    if(!confirmBtn) return;
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Connecting...';
                    if(errorBox) { errorBox.classList.add('hidden'); errorBox.textContent = ''; }

                    try{
                        const resp = await fetch("{{ route('dashboard.connect') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify({})
                        });

                        if(!resp.ok){
                            const err = await resp.json().catch(() => ({}));
                            const message = err.message || (err.error ? err.error : 'Unable to build router login URL');
                            if(errorBox){ errorBox.textContent = message; errorBox.classList.remove('hidden'); }
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = 'Confirm & Connect';
                            return;
                        }

                        const data = await resp.json();

                        // Redirect the browser to the router login URL (GET)
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                            return;
                        } else {
                            if(errorBox){ errorBox.textContent = 'Router login URL not returned by server.'; errorBox.classList.remove('hidden'); }
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = 'Confirm & Connect';
                            return;
                        }

                        const username = data.username;
                        const password = data.password;
                        const routerUrl = data.login_url || data.loginUrl || "{{ config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL', 'http://192.168.88.1/login') }}";
                        const dashboardUrl = data.dashboard_url || "{{ route('dashboard') }}";

                        if(!username || !password){
                            if(errorBox){ errorBox.textContent = 'Missing credentials received from server.'; errorBox.classList.remove('hidden'); }
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = 'Confirm & Connect';
                            return;
                        }

                        // Build GET-based login URL and navigate (bypasses mixed-content POST block)
                        const loginUrl = routerUrl
                            + '?username=' + encodeURIComponent(username)
                            + '&password=' + encodeURIComponent(password)
                            + '&dst=' + encodeURIComponent(dashboardUrl);

                        // Redirect the whole page to the router login URL (GET)
                        window.location.href = loginUrl;

                        // Navigation will occur; no further UI updates are necessary.

                    }catch(e){
                        console.error('Error during connect to router flow', e);
                        if(errorBox){ errorBox.textContent = 'Unexpected error. Please try again.'; errorBox.classList.remove('hidden'); }
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm & Connect';
                    }
                });
            })();
        </script>

    </div>
</div>