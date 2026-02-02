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
                @elseif($connectionStatus === 'unknown')
                    <div class="flex items-center space-x-3 mt-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-60"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500 ring-2 ring-yellow-300"></span>
                        </span>
                        <p class="text-sm font-semibold">
                            <span class="text-yellow-600 dark:text-yellow-400">Connection status unknown</span>
                            <span class="ml-2 text-yellow-600 dark:text-yellow-400">IP: {{ $currentIp }}</span>
                        </p>
                    </div>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Unable to verify connection - RADIUS server unreachable</p>
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
                        <div class="text-6xl font-black text-white mb-2">{{ $formattedTotalUsed }}</div>
                        <div class="text-blue-100 text-lg mb-6">{{ $connectionStatus === 'active' ? 'Current session' : 'Total used' }}</div>
                        
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

            @if($user->pendingSubscriptions->count() > 0)
                <div class="col-span-2 mt-6">
                    <h3 class="text-gray-600 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-3">UP NEXT (Queue)</h3>

                    <div class="space-y-3">
                        @foreach($user->pendingSubscriptions as $queueItem)
                            <div class="bg-gray-100 dark:bg-gray-700/70 border border-gray-200 dark:border-gray-600 rounded-xl p-4 flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-yellow-500/20 text-yellow-500 flex items-center justify-center font-bold text-sm">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div>
                                        <h4 class="text-gray-900 dark:text-white font-bold">{{ $queueItem->plan->name }}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">
                                            Data: {{ \Illuminate\Support\Number::fileSize($queueItem->plan->data_limit) }}
                                            | Duration: {{ $queueItem->plan->validity_days }} Days
                                        </p>
                                    </div>
                                </div>

                                @if($loop->first)
                                     <button wire:click="forceActivate({{ $queueItem->id }})" wire:confirm="Activate this now? Current plan will be stopped." class="text-xs bg-yellow-500 text-black font-bold px-3 py-1 rounded-lg hover:bg-yellow-400 transition-colors">
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
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white">Hot Deals</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($plans as $plan)
                        <div class="bg-gradient-to-br from-blue-600 to-blue-400 rounded-3xl shadow-lg transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                            <div class="text-center space-y-2 p-1">
                                <div class="bg-white py-4 rounded-3xl m-1">
                                    <div class="text-2xl font-black text-blue-600">{{ $plan->validity_days }}</div>
                                    <div class="text-gray-400 text-xs font-bold uppercase tracking-wide">Days</div>
                                    <div class="border-t border-gray-100 pt-2 mt-2">
                                        <div class="text-xl font-black text-gray-800">{{ \Illuminate\Support\Number::fileSize($plan->data_limit) }}</div>
                                        <div class="text-gray-400 text-xs">Data</div>
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
    </div>
</div>