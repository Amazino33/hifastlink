<div class="px-4 py-6 md:px-6 lg:px-8">
    <div wire:poll.10s class="mb-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-3xl p-8 mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                        Hi, {{ $user->display_name }} 👋
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">Welcome back to your dashboard</p>
                    @if($isDeviceOnline)
                        <div class="flex items-center space-x-3 mt-2">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                                <span
                                    class="relative inline-flex rounded-full h-3 w-3 bg-green-500 ring-2 ring-green-300"></span>
                            </span>
                            <p class="text-sm font-semibold">
                                <span class="text-green-600 dark:text-green-400">Online now (This Device)</span>
                                @php
                                    $currentMacKey = session('current_device_mac') ? preg_replace('/[^a-f0-9]/', '', strtolower(session('current_device_mac'))) : null;
                                    $currentSession = $currentMacKey ? ($activeSession_->get($currentMacKey) ?? null) : null;
                                @endphp
                                    <span class="ml-2 text-green-600 dark:text-green-400">IP:
                                        {{ $currentSession ? $currentSession->framedipaddress : 'Offline' }}
                                    </span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 mt-1">
                            <i class="fa-solid fa-mobile-screen-button text-xs text-gray-500 dark:text-gray-400"></i>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Connected Devices: <span
                                    class="font-semibold text-gray-700 dark:text-gray-300">{{ $connectedDevices }}/{{ $isAdminUser ? '∞' : $maxDevices }}</span>
                                <span class="text-green-600 dark:text-green-400 font-semibold">(This device is
                                    connected)</span>
                            </p>
                        </div>
                        @if($currentLocation)
                            <div class="flex items-center space-x-2 mt-1">
                                <i class="fa-solid fa-location-dot text-xs text-blue-500 dark:text-blue-400"></i>
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    Connected via: <span class="font-semibold">{{ $currentLocation }}</span>
                                </p>
                            </div>
                        @endif
                    @elseif($connectedDevices > 0)
                        <div class="flex items-center space-x-3 mt-2">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-60"></span>
                                <span
                                    class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500 ring-2 ring-yellow-300"></span>
                            </span>
                            <p class="text-sm font-semibold">
                                <span class="text-yellow-600 dark:text-yellow-400">Other Devices Online</span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 mt-1">
                            <i class="fa-solid fa-mobile-screen-button text-xs text-gray-500 dark:text-gray-400"></i>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Connected Devices: <span
                                    class="font-semibold text-gray-700 dark:text-gray-300">{{ $connectedDevices }}/{{ $isAdminUser ? '∞' : $maxDevices }}</span>
                                <span class="text-gray-500 dark:text-gray-400">(Not this device)</span>
                            </p>
                        </div>
                        @if($currentLocation)
                            <div class="flex items-center space-x-2 mt-1">
                                <i class="fa-solid fa-location-dot text-xs text-blue-500 dark:text-blue-400"></i>
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    Connected via: <span class="font-semibold">{{ $currentLocation }}</span>
                                </p>
                            </div>
                        @endif
                    @elseif($connectionStatus === 'active')
                        <div class="flex items-center space-x-3 mt-2">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
                                <span
                                    class="relative inline-flex rounded-full h-3 w-3 bg-green-500 ring-2 ring-green-300"></span>
                            </span>
                            <p class="text-sm font-semibold">
                                <span class="text-green-600 dark:text-green-400">Online now</span>
                                <span class="ml-2 text-green-600 dark:text-green-400">IP:
                                    {{ $currentIp === 'Connected' ? 'Connected' : $currentIp }}</span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-2 mt-1">
                            <i class="fa-solid fa-mobile-screen-button text-xs text-gray-500 dark:text-gray-400"></i>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Connected Devices: <span
                                    class="font-semibold text-gray-700 dark:text-gray-300">{{ $connectedDevices }}/{{ $isAdminUser ? '∞' : $maxDevices }}</span>
                            </p>
                        </div>
                        @if($currentLocation)
                            <div class="flex items-center space-x-2 mt-1">
                                <i class="fa-solid fa-location-dot text-xs text-blue-500 dark:text-blue-400"></i>
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    Connected via: <span class="font-semibold">{{ $currentLocation }}</span>
                                </p>
                            </div>
                        @endif
                    @elseif($connectionStatus === 'unknown')
                        <div class="flex items-center space-x-3 mt-2">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-60"></span>
                                <span
                                    class="relative inline-flex rounded-full h-3 w-3 bg-blue-500 ring-2 ring-blue-300"></span>
                            </span>
                            <p class="text-sm font-semibold">
                                <span class="text-blue-600 dark:text-blue-400">Connection status unknown</span>
                                <span class="ml-2 text-blue-600 dark:text-blue-400">IP: {{ $currentIp }}</span>
                            </p>
                        </div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Unable to verify connection - RADIUS server
                            unreachable</p>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">Currently offline</p>
                    @endif


                    <div class="flex md:hidden items-center justify-start space-x-3 mt-6 gap-2">
                        @if(Auth::user()->is_family_admin)
                            <a href="{{ route('family') }}"
                                class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                                <i class="fa-solid fa-users text-gray-600 dark:text-gray-300 text-xl"></i>
                            </a>
                        @endif

                        <button
                            class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                            <i class="fa-solid fa-bell text-gray-600 dark:text-gray-300 text-xl"></i>
                        </button>

                        <button
                            class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                            <i class="fa-solid fa-gear text-gray-600 dark:text-gray-300 text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    @if(Auth::user()->is_family_admin)
                        <a href="{{ route('family') }}"
                            class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                            <i class="fa-solid fa-users text-gray-600 dark:text-gray-300 text-xl"></i>
                        </a>
                    @endif
                    <button
                        class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
                        <i class="fa-solid fa-bell text-gray-600 dark:text-gray-300 text-xl"></i>
                    </button>
                    <button
                        class="p-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl transition-all duration-300">
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

        {{-- Low data / expiry warning banners — never shown for admin accounts --}}
        @if($subscriptionStatus === 'active' && !$isAdminUser)
            @if(($dataUsagePercentage ?? 0) >= 90)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4 flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-xl flex-shrink-0"></i>
                    <div>
                        <p class="text-sm font-bold text-red-800 dark:text-red-300">Critical: Data almost exhausted</p>
                        <p class="text-xs text-red-600 dark:text-red-400">You have used {{ $dataUsagePercentage }}% of your data. Top up now to avoid disconnection.</p>
                    </div>
                    <a href="#hot-deals" class="ml-auto text-xs font-bold bg-red-500 text-white px-3 py-1.5 rounded-lg hover:bg-red-600 transition-colors flex-shrink-0">Top Up</a>
                </div>
            @elseif(($dataUsagePercentage ?? 0) >= 70)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-4 flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-500 text-xl flex-shrink-0"></i>
                    <div>
                        <p class="text-sm font-bold text-yellow-800 dark:text-yellow-300">Warning: Data running low</p>
                        <p class="text-xs text-yellow-600 dark:text-yellow-400">You have used {{ $dataUsagePercentage }}% of your data allowance.</p>
                    </div>
                    <a href="#hot-deals" class="ml-auto text-xs font-bold bg-yellow-500 text-white px-3 py-1.5 rounded-lg hover:bg-yellow-600 transition-colors flex-shrink-0">Buy More</a>
                </div>
            @endif
            @if(($subscriptionDays ?? 99) <= 3)
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4 mb-4 flex items-center gap-3">
                    <i class="fa-solid fa-clock text-orange-500 text-xl flex-shrink-0"></i>
                    <div>
                        <p class="text-sm font-bold text-orange-800 dark:text-orange-300">
                            Plan expiring {{ ($subscriptionDays ?? 0) === 0 ? 'today' : 'in ' . ($subscriptionDays ?? 0) . ' ' . (($subscriptionDays ?? 0) === 1 ? 'day' : 'days') }}
                        </p>
                        <p class="text-xs text-orange-600 dark:text-orange-400">Renew before {{ $validUntil }} to avoid losing connectivity.</p>
                    </div>
                    <a href="#hot-deals" class="ml-auto text-xs font-bold bg-orange-500 text-white px-3 py-1.5 rounded-lg hover:bg-orange-600 transition-colors flex-shrink-0">Renew</a>
                </div>
            @endif
        @endif

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                @if($isAdminUser)
                {{-- ══════════════════════════════════════════ --}}
                {{-- ADMIN HERO — Network Control Center       --}}
                {{-- ══════════════════════════════════════════ --}}
                <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-8 shadow-2xl relative overflow-hidden border border-white/10">
                    <div class="absolute inset-0 pointer-events-none overflow-hidden">
                        <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-blue-600/8 rounded-full blur-3xl"></div>
                    </div>

                    <div class="relative z-10">
                        {{-- Header --}}
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-blue-500/20 border border-blue-400/30 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-900/50">
                                    <i class="fa-solid fa-shield-halved text-blue-300 text-2xl"></i>
                                </div>
                                <div>
                                    <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-1">Network Control Center</p>
                                    <h2 class="text-white text-2xl font-black leading-tight">{{ $user->name }}</h2>
                                    <div class="flex flex-wrap items-center gap-2 mt-2">
                                        <span class="px-2.5 py-1 bg-blue-500/20 text-blue-300 border border-blue-400/30 text-xs font-bold rounded-full uppercase tracking-wider">Administrator</span>
                                        <span class="flex items-center gap-1.5 text-xs font-semibold {{ $radiusReachable ? 'text-green-400' : 'text-red-400' }}">
                                            <span class="w-2 h-2 rounded-full {{ $radiusReachable ? 'bg-green-400 animate-pulse' : 'bg-red-500' }}"></span>
                                            RADIUS {{ $radiusReachable ? 'Online' : 'Offline' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Connection controls --}}
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                @if($isDeviceOnline)
                                    <span class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-full text-xs font-bold bg-green-500/20 border border-green-400/30 text-green-300">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
                                        </span>
                                        ONLINE
                                    </span>
                                    <form id="disconnect-form" action="{{ route('user.disconnect') }}" method="POST">
                                        @csrf
                                        @if(session('current_device_mac'))
                                            <input type="hidden" name="mac" value="{{ session('current_device_mac') }}">
                                        @endif
                                        <button type="submit" id="disconnect-btn"
                                            class="w-full sm:w-auto px-4 py-2 text-xs font-semibold rounded-lg bg-red-600/80 hover:bg-red-600 text-white transition-all">
                                            <i class="fa-solid fa-power-off mr-1"></i>Disconnect
                                        </button>
                                    </form>
                                @elseif($connectedDevices > 0)
                                    <span class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-full text-xs font-bold bg-yellow-500/20 border border-yellow-400/30 text-yellow-300">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-400"></span>
                                        </span>
                                        OTHER DEVICES
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-full text-xs font-bold bg-white/10 border border-white/10 text-white/40">
                                        OFFLINE
                                    </span>
                                @endif

                                @if(!$showDisconnectButton)
                                    <button type="button" data-connect-btn
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold rounded-lg bg-blue-600 hover:bg-blue-500 text-white transition-all shadow-lg shadow-blue-900/50">
                                        <i class="fa-solid fa-wifi"></i>Connect
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Network stats grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="bg-white/5 border border-white/8 rounded-2xl p-4 hover:bg-white/8 transition-colors group">
                                <div class="text-3xl font-black text-white mb-1 group-hover:text-blue-300 transition-colors">{{ $networkStats['users_online'] }}</div>
                                <div class="text-blue-400 text-xs font-bold uppercase tracking-wide">Users Online</div>
                                <div class="text-white/30 text-xs mt-0.5">{{ $networkStats['active_sessions'] }} sessions</div>
                            </div>
                            <div class="bg-white/5 border border-white/8 rounded-2xl p-4 hover:bg-white/8 transition-colors group">
                                <div class="text-3xl font-black text-white mb-1 group-hover:text-blue-300 transition-colors">{{ $networkStats['today_bytes_human'] }}</div>
                                <div class="text-blue-400 text-xs font-bold uppercase tracking-wide">Data Today</div>
                                <div class="text-white/30 text-xs mt-0.5">Network throughput</div>
                            </div>
                            <div class="bg-white/5 border border-white/8 rounded-2xl p-4 hover:bg-white/8 transition-colors group">
                                <div class="text-3xl font-black text-white mb-1 group-hover:text-blue-300 transition-colors">{{ $networkStats['total_users'] }}</div>
                                <div class="text-blue-400 text-xs font-bold uppercase tracking-wide">Total Users</div>
                                <div class="text-white/30 text-xs mt-0.5">Registered accounts</div>
                            </div>
                            <div class="bg-white/5 border border-white/8 rounded-2xl p-4 hover:bg-white/8 transition-colors group">
                                <div class="text-3xl font-black text-white mb-1 group-hover:text-blue-300 transition-colors">{{ $networkStats['active_vouchers'] }}</div>
                                <div class="text-blue-400 text-xs font-bold uppercase tracking-wide">Vouchers</div>
                                <div class="text-white/30 text-xs mt-0.5">Unused / available</div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div
                    class="bg-gradient-to-r from-blue-600 to-blue-400 rounded-3xl p-8 shadow-2xl relative overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                    <div class="absolute inset-0 opacity-20">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse">
                        </div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
                            <span class="text-blue-100 text-sm font-semibold uppercase tracking-wide">Your
                                Subscription</span>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                                @if($isDeviceOnline)
                                    <span id="connection-badge"
                                        class="relative inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-bold bg-green-500 text-white shadow-lg">
                                        <span id="online-indicator" class="relative inline-flex mr-2">
                                            <span
                                                class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                                        </span>
                                        <span id="connection-text">ONLINE</span>
                                    </span>
                                @elseif($connectedDevices > 0)
                                    <span id="connection-badge"
                                        class="relative inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-bold bg-yellow-500 text-white shadow-lg">
                                        <span id="online-indicator" class="relative inline-flex mr-2">
                                            <span
                                                class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-white opacity-75"></span>
                                            <span class="relative inline-flex h-2 w-2 rounded-full bg-white"></span>
                                        </span>
                                        <span id="connection-text">OTHER DEVICES</span>
                                    </span>
                                @else
                                    <span id="connection-badge"
                                        class="relative inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-bold bg-gray-600 text-white shadow-lg">
                                        <span id="connection-text">OFFLINE</span>
                                    </span>
                                @endif

                                <div id="connection-buttons" class="flex items-center"
                                    data-connected-devices="{{ $connectedDevices }}"
                                    data-max-devices="{{ $maxDevices }}">
                                    @if($showDisconnectButton)
                                        <form id="disconnect-form" action="{{ route('user.disconnect') }}" method="POST"
                                            class="w-full sm:w-auto">
                                            @csrf
                                            @if(session('current_device_mac'))
                                                <input type="hidden" name="mac" value="{{ session('current_device_mac') }}">
                                            @endif
                                            <button type="submit" id="disconnect-btn"
                                                class="w-full sm:w-auto px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-blue-600">
                                                <i class="fa-solid fa-power-off mr-1"></i>Disconnect
                                            </button>
                                        </form>
                                    @else
                                        @if($subscriptionStatus === 'active')
                                            @if($isAdminUser || $connectedDevices < $maxDevices)
                                                <button type="button" data-connect-btn
                                                    class="w-full sm:w-auto inline-block text-center px-4 py-2 text-xs font-semibold rounded-lg bg-white/20 hover:bg-white/30 text-white transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-blue-600">
                                                    <i class="fa-solid fa-wifi mr-1"></i>Connect to Router
                                                </button>
                                            @else
                                                <span
                                                    class="w-full sm:w-auto inline-block text-center px-4 py-2 text-xs font-semibold rounded-lg bg-gray-500/50 text-white/70 cursor-not-allowed shadow-lg">
                                                    <i class="fa-solid fa-ban mr-1"></i>Device Limit Reached
                                                </span>
                                            @endif
                                        @else
                                            <a href="#hot-deals"
                                                class="w-full sm:w-auto inline-block text-center px-4 py-2 text-xs font-semibold rounded-lg bg-blue-500 hover:bg-blue-600 text-white transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                                                <i class="fa-solid fa-shopping-cart mr-1"></i>Subscribe Now
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            @if($isAdminUser)
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-6xl font-black text-white">∞</span>
                                    <span class="self-center px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30 tracking-widest uppercase">Admin</span>
                                </div>
                                <div class="text-sm text-white/80 font-semibold mb-3">Unlimited access — no restrictions</div>
                                <div class="text-blue-100 text-lg">Unlimited Data</div>
                                <div class="text-xs text-blue-200 mt-1 font-medium tracking-wide uppercase">No plan required</div>
                            @elseif($isFreePass)
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-6xl font-black text-white">∞</span>
                                    <span class="self-center px-3 py-1 rounded-full text-xs font-bold bg-blue-500 text-white tracking-widest uppercase">Free Pass</span>
                                </div>
                                <div class="text-sm text-white/80 font-semibold mb-3">Unlimited data — up to 2 devices</div>
                                <div class="text-blue-100 text-lg">Unlimited Data</div>
                                <div class="text-xs text-blue-200 mt-1 font-medium tracking-wide uppercase">Free pass</div>
                            @elseif($subscriptionStatus === 'active')
                                <div class="text-6xl font-black text-white mb-2">{{ $subscriptionDays }}</div>
                                <div class="text-sm text-white/80 font-semibold mb-3">
                                    {{ $subscriptionDays === 1 ? 'day remaining' : 'days remaining' }}</div>
                                <div class="text-blue-100 text-lg">
                                    {{ $formattedDataLimit }}
                                    @if($hasRollover ?? false)
                                        <span class="text-xs text-blue-200 font-normal ml-1">(Plan + Rollover)</span>
                                    @endif
                                </div>
                                <div class="text-xs text-blue-200 mt-1 font-medium tracking-wide uppercase">
                                    Total Available Data
                                </div>
                                @if($hasRollover ?? false)
                                    <div class="text-xs text-blue-100 mt-2 flex items-center gap-1">
                                        <i class="fa-solid fa-rotate text-yellow-300"></i>
                                        Includes <strong class="ml-0.5">{{ $user->formatBytes($user->rollover_available_bytes) }}</strong>&nbsp;rollover
                                        (valid for {{ $user->rollover_validity_days }} more days)
                                    </div>
                                @endif
                            @elseif($subscriptionStatus === 'exhausted')
                                <div class="flex items-center space-x-3">
                                    <div class="text-6xl font-black text-white mb-2">Data Exhausted</div>
                                    @if($user->display_status === 'PLAN EXPIRED')
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white">Plan
                                            Expired</span>
                                    @endif
                                </div>
                                <div class="text-blue-100 text-lg">0 MB remaining</div>
                            @else
                                @if($hasVoucherAccess)
                                    <div class="text-6xl font-black text-white mb-2">{{ $subscriptionDays }}</div>
                                    <div class="text-sm text-white/80 font-semibold mb-3">
                                        {{ $subscriptionDays === 1 ? 'day remaining' : 'days remaining' }}</div>
                                    <div class="text-blue-100 text-lg">{{ $formattedDataLimit }}</div>
                                    <div class="text-xs text-blue-200 mt-1 font-medium tracking-wide uppercase">Voucher Access</div>
                                @else
                                    <div class="text-6xl font-black text-white mb-2">No Active Plan</div>
                                    <div class="text-blue-100 text-lg">You have no active data plan. Please subscribe to use
                                        data services.</div>
                                @endif
                            @endif

                            @if($isDeviceOnline)
                                <div class="mt-4 space-y-2">
                                    @if($currentLocation)
                                        <div class="flex items-center text-sm mb-2">
                                            <i class="fa-solid fa-broadcast-tower mr-2 text-yellow-300"></i>
                                            <span class="text-yellow-100 font-semibold">{{ $currentLocation }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center text-sm">
                                        <i class="fa-solid fa-network-wired mr-2 text-blue-200"></i>
                                        <span class="text-blue-100">IP:
                                            {{ $currentIp === 'Connected' ? 'Connected' : $currentIp }}</span>
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
                @endif

                @if($isAdminUser)
                {{-- ══════════════════════════════════════════ --}}
                {{-- ADMIN QUICK ACTIONS + MY ACTIVITY         --}}
                {{-- ══════════════════════════════════════════ --}}
                <div class="bg-gradient-to-br from-slate-800 via-slate-800 to-slate-900 rounded-3xl shadow-2xl relative overflow-hidden border border-white/5">
                    <div class="absolute inset-0 pointer-events-none overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600/5 rounded-full blur-3xl"></div>
                    </div>
                    <div class="relative z-10 p-8">
                        <p class="text-white/40 text-xs font-bold uppercase tracking-widest mb-4">Quick Actions</p>
                        <div class="grid grid-cols-2 gap-3 mb-8">
                            <a href="/admin"
                                class="flex items-center gap-3 bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/30 rounded-xl px-4 py-3 transition-all group">
                                <div class="w-9 h-9 bg-blue-500/30 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-blue-500/50 transition-colors">
                                    <i class="fa-solid fa-gauge-high text-blue-300 text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-white text-xs font-bold">Admin Panel</div>
                                    <div class="text-white/40 text-xs">Filament dashboard</div>
                                </div>
                                <i class="fa-solid fa-arrow-right text-white/20 ml-auto text-xs group-hover:text-blue-300 transition-colors flex-shrink-0"></i>
                            </a>
                            <a href="/admin/users"
                                class="flex items-center gap-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl px-4 py-3 transition-all group">
                                <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-white/20 transition-colors">
                                    <i class="fa-solid fa-users text-white/60 text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-white text-xs font-bold">Manage Users</div>
                                    <div class="text-white/40 text-xs">Accounts & roles</div>
                                </div>
                                <i class="fa-solid fa-arrow-right text-white/20 ml-auto text-xs flex-shrink-0"></i>
                            </a>
                            <a href="{{ route('vouchers.index') }}"
                                class="flex items-center gap-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl px-4 py-3 transition-all group">
                                <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-white/20 transition-colors">
                                    <i class="fa-solid fa-ticket text-white/60 text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-white text-xs font-bold">Vouchers</div>
                                    <div class="text-white/40 text-xs">Create & manage</div>
                                </div>
                                <i class="fa-solid fa-arrow-right text-white/20 ml-auto text-xs flex-shrink-0"></i>
                            </a>
                            <a href="/admin/plans"
                                class="flex items-center gap-3 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl px-4 py-3 transition-all group">
                                <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-white/20 transition-colors">
                                    <i class="fa-solid fa-layer-group text-white/60 text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-white text-xs font-bold">Data Plans</div>
                                    <div class="text-white/40 text-xs">Packages & pricing</div>
                                </div>
                                <i class="fa-solid fa-arrow-right text-white/20 ml-auto text-xs flex-shrink-0"></i>
                            </a>
                        </div>

                        <div class="border-t border-white/10 pt-6">
                            <p class="text-white/40 text-xs font-bold uppercase tracking-widest mb-4">My Activity</p>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 text-white/60 text-sm">
                                        <i class="fa-solid fa-database text-blue-400 w-4 text-center text-xs"></i>
                                        Data Used (RADIUS)
                                    </span>
                                    <span class="text-white font-bold text-sm">{{ $formattedTotalUsed }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 text-white/60 text-sm">
                                        <i class="fa-solid fa-mobile-screen-button text-blue-400 w-4 text-center text-xs"></i>
                                        Connected Devices
                                    </span>
                                    <span class="text-white font-bold text-sm">{{ $connectedDevices }} <span class="text-white/30 font-normal">/ ∞</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 text-white/60 text-sm">
                                        <i class="fa-solid fa-clock text-blue-400 w-4 text-center text-xs"></i>
                                        Session Uptime
                                    </span>
                                    <span class="text-white font-bold text-sm">{{ $uptime }}</span>
                                </div>
                                @if($isDeviceOnline && $currentIp && $currentIp !== 'N/A')
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2 text-white/60 text-sm">
                                        <i class="fa-solid fa-network-wired text-blue-400 w-4 text-center text-xs"></i>
                                        IP Address
                                    </span>
                                    <span class="text-white font-mono font-bold text-sm">{{ $currentIp }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div
                    class="bg-gradient-to-br from-indigo-600 via-blue-500 to-teal-400 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse">
                        </div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <div class="text-blue-100 text-sm font-semibold uppercase tracking-wide mb-2">
                                    {{ $connectionStatus === 'active' ? 'Live Data Usage' : 'Data Usage' }}
                                </div>
                                @if($isAdminUser)
                                    <div class="text-white text-lg font-bold mb-1 flex items-center gap-2">
                                        Administrator
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30">Admin</span>
                                    </div>
                                    <div class="text-white/80 text-xs">Unrestricted access — no plan required</div>
                                @elseif($isFreePass)
                                    <div class="text-white text-lg font-bold mb-1 flex items-center gap-2">
                                        Free Pass
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-500 text-white">Free Pass</span>
                                    </div>
                                    <div class="text-white/80 text-xs">Unlimited data — 2 device limit</div>
                                @else
                                    <div class="text-white text-lg font-bold mb-1">
                                        {{ $user->plan->name ?? ($hasVoucherAccess ? 'Voucher Access' : 'No Active Plan') }}</div>
                                    @if($subscriptionStatus !== 'inactive')
                                        <div class="text-white/80 text-xs">Valid Until: {{ $validUntil }}
                                            ({{ $planValidityHuman }})</div>
                                    @endif
                                @endif
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
                            <div class="text-blue-100 text-lg mb-6">
                                {{ $subscriptionStatus === 'exhausted' ? 'Data Exhausted' : ($connectionStatus === 'active' ? 'Current session' : 'Total used') }}
                            </div>

                            @if($subscriptionStatus !== 'inactive' && $formattedDataLimit !== 'Unlimited')
                                @php
                                    // Usage-based percentage: prefer the controller's family-aware percentage; fallback to the user's own accessor
                                    $usedPercent = (int) ($dataUsagePercentage ?? ($user->data_usage_percentage ?? 0));
                                    $pct = min(100, max(0, $usedPercent));

                                    // Color logic: 0-70 Safe, 71-90 Warning, 91-100 Danger
                                    $barGradient = $pct <= 70 ? 'from-green-400 to-green-600' : ($pct <= 90 ? 'from-yellow-400 via-orange-400 to-orange-600' : 'from-red-500 to-red-700');
                                @endphp

                                <div class="flex items-center justify-between mb-2 text-xs text-blue-100">
                                    <div class="font-semibold">@if($subscriptionStatus === 'exhausted') 0 MB used @else
                                    {{ $formattedTotalUsed }} used @endif</div>
                                    <div class="font-medium">{{ $pct }}%</div>
                                </div>

                                <div class="relative h-4 bg-white/20 rounded-full overflow-hidden">
                                    <div class="absolute inset-0 rounded-full bg-gradient-to-r {{ $barGradient }} transition-all duration-500"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-blue-100 mt-2">
                                    <span class="text-sm">@if($subscriptionStatus === 'exhausted') 0 MB used @else
                                    {{ $formattedTotalUsed }} used @endif</span>
                                    <span class="text-sm">{{ $formattedDataLimit }} total</span>
                                </div>
                            @else
                                @if($subscriptionStatus === 'inactive')
                                    <div class="text-sm text-blue-100 mt-2">No usage to display — subscribe to a plan to start
                                        using data.</div>
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
                @endif

                @if($user->pendingSubscriptions->count() > 0)
                    <div class="col-span-2 mt-6">
                        <h3 class="text-gray-600 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-3">UP NEXT
                            (Queue)</h3>

                        <div class="space-y-3">
                            @foreach($user->pendingSubscriptions as $queueItem)
                                <div
                                    class="bg-gray-100 dark:bg-gray-700/70 border border-gray-200 dark:border-gray-600 rounded-xl p-4 flex items-center justify-between shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-500 flex items-center justify-center font-bold text-sm">
                                            {{ $loop->iteration }}
                                        </div>
                                        <div>
                                            <h4 class="text-gray-900 dark:text-white font-bold">{{ $queueItem->plan->name }}
                                            </h4>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                Data: {{ $user->data_remaining_human }}
                                                | Duration: {{ $queueItem->plan->validity_days }} Days
                                            </p>
                                        </div>
                                    </div>

                                    @if($loop->first)
                                        <button wire:click="forceActivate({{ $queueItem->id }})"
                                            wire:confirm="Activate this now? Current plan will be stopped."
                                            class="text-xs bg-blue-500 text-white font-bold px-3 py-1 rounded-lg hover:bg-blue-600 transition-colors">
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
                                <h3 class="text-lg font-bold text-white">Redeem a Voucher</h3>
                                <p class="text-white/70 text-xs">Enter your code to activate data on your account</p>
                            </div>
                        </div>

                        <form wire:submit.prevent="redeemVoucher">
                            <div class="relative">
                                <input
                                    wire:model="voucherCode"
                                    type="text"
                                    placeholder="e.g. VCH-ABCD1234"
                                    autocomplete="off"
                                    class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/50 focus:ring-2 focus:ring-blue-300 focus:border-transparent font-mono uppercase tracking-widest text-center"
                                >
                            </div>
                            @error('voucherCode')
                                <span class="text-red-200 text-xs mt-2 block text-center">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}
                                </span>
                            @enderror
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75 cursor-not-allowed"
                                class="w-full mt-3 bg-white text-blue-900 font-bold py-2.5 rounded-xl hover:bg-gray-100 transition-colors shadow-lg flex items-center justify-center gap-2"
                            >
                                <span wire:loading.remove wire:target="redeemVoucher">
                                    <i class="fa-solid fa-ticket mr-1"></i> Redeem Code
                                </span>
                                <span wire:loading wire:target="redeemVoucher" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Activating...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <div id="devices" class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Your Devices</h3>
                        <a href="#devices" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">Manage</a>
                    </div>

                    @if(isset($devices) && $devices->count())
                        <div class="space-y-3">
                            @foreach($devices as $device)
                                @php
                                    $macKey = preg_replace('/[^a-f0-9]/', '', strtolower($device->mac));
                                    $deviceSession = $activeSession_->get($macKey);
                                    $displayIp = $deviceSession ? $deviceSession->framedipaddress : ($device->ip ?? 'N/A');
                                    $isThisDevice = session('current_device_mac') === $device->mac;
                                @endphp
                                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700 border border-gray-100 dark:border-gray-600">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full {{ $device->is_connected ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-400' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                                                <i class="fa-solid fa-wifi"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ strtoupper($device->mac) }}
                                                @if($isThisDevice)
                                                    <span class="ml-2 text-xs font-medium text-blue-600 dark:text-blue-400">(This device)</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ $device->router?->name ?? 'Unknown Router' }} &middot; IP: {{ $displayIp }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right space-y-1">
                                        <div class="text-sm font-medium {{ $device->is_connected ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                            {{ $device->is_connected ? 'Connected' : 'Offline' }}
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $device->last_seen ? $device->last_seen->diffForHumans() : '—' }}
                                        </div>
                                        <div class="flex items-center justify-end gap-2 mt-1">
                                            @if($device->is_connected)
                                                <button
                                                    wire:click="disconnectDevice({{ $device->id }})"
                                                    wire:confirm="Disconnect this device from the network?"
                                                    class="text-xs text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium hover:underline">
                                                    Disconnect
                                                </button>
                                            @endif
                                            @if($isThisDevice)
                                                @if(data_get($device->meta, 'browser_token_hash'))
                                                    <button wire:click="forgetDevice({{ $device->id }})"
                                                        class="text-xs text-gray-500 dark:text-gray-400 hover:underline">Forget</button>
                                                @else
                                                    <button wire:click="claimDevice({{ $device->id }})"
                                                        class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Claim</button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            {{ $devices->links() }}
                        </div>
                    @else
                        <div class="text-sm text-gray-500 dark:text-gray-400">No devices recorded yet.</div>
                    @endif
                </div>

                {{-- My Vouchers — only shown to users who have created vouchers --}}
                @if($myVouchers->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">
                            <i class="fa-solid fa-ticket mr-2 text-blue-500"></i>My Vouchers
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $myVouchers->total() }} voucher{{ $myVouchers->total() !== 1 ? 's' : '' }} total
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($myVouchers as $v)
                        @php
                            [$badgeClass, $badgeLabel] = match($v['status']) {
                                'active'    => ['bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400', 'Active'],
                                'idle'      => ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300', 'Idle'],
                                'exhausted' => ['bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400', 'Exhausted'],
                                'expired'   => ['bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400', 'Expired'],
                                default     => ['bg-gray-100 text-gray-600', ucfirst($v['status'])],
                            };
                        @endphp
                        <div class="border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden">
                            {{-- Voucher header row --}}
                            <div class="flex flex-wrap items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700/50">
                                <span class="font-mono font-bold text-gray-800 dark:text-gray-200 text-sm tracking-widest">{{ $v['code'] }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $v['plan'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Slots: <strong class="text-gray-700 dark:text-gray-300">{{ $v['used'] }}/{{ $v['max'] }}</strong></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Total used: <strong class="text-gray-700 dark:text-gray-300">{{ $v['data_used'] }}</strong></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto">Expires: {{ $v['expires_at'] }}</span>
                            </div>
                            {{-- Live sessions (shown when someone is connected on this voucher) --}}
                            @if(!empty($v['sessions']))
                                <div class="p-4">
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                        </span>
                                        {{ $v['online'] }} active connection{{ $v['online'] > 1 ? 's' : '' }}
                                    </p>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="text-left text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700">
                                                    <th class="pb-2 pr-4 font-semibold">IP Address</th>
                                                    <th class="pb-2 pr-4 font-semibold">MAC / Device</th>
                                                    <th class="pb-2 pr-4 font-semibold">Duration</th>
                                                    <th class="pb-2 font-semibold">Data This Session</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                                @foreach($v['sessions'] as $s)
                                                <tr>
                                                    <td class="py-2 pr-4 font-mono text-gray-700 dark:text-gray-300">{{ $s['ip'] }}</td>
                                                    <td class="py-2 pr-4 font-mono text-gray-500 dark:text-gray-400">{{ $s['mac'] }}</td>
                                                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $s['duration'] }}</td>
                                                    <td class="py-2 text-gray-600 dark:text-gray-400">{{ $s['data'] }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="px-4 py-3 text-xs text-gray-400 dark:text-gray-600">No active connections on this voucher.</div>
                            @endif
                        </div>
                        @endforeach

                        @if($myVouchers->hasPages())
                            <div class="mt-4">
                                {{ $myVouchers->links() }}
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 id="hot-deals" class="text-2xl font-black text-gray-900 dark:text-white">Hot Deals</h3>
                        <a href="{{ route('pricing') }}"
                            class="text-sm font-bold text-primary hover:text-secondary transition-colors">
                            Show All <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($plans->take(4) as $plan)
                            <div
                                class="bg-gradient-to-br from-blue-600 to-blue-400 rounded-3xl shadow-lg transform hover:-translate-y-2 transition-all duration-300 cursor-pointer group">
                                <div class="text-center space-y-2 p-1">
                                    <div class="bg-white dark:bg-gray-700 py-4 rounded-3xl m-1">
                                        <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $plan->validity_days }}</div>
                                        <div class="text-gray-400 dark:text-gray-300 text-xs font-bold uppercase tracking-wide">Days</div>
                                        <div class="border-t border-gray-100 dark:border-gray-600 pt-2 mt-2">
                                            <div class="text-xl font-black text-gray-800 dark:text-white">{{ $plan->data_limit_human }}
                                            </div>
                                            <div class="text-gray-400 dark:text-gray-300 text-xs">Data</div>
                                        </div>
                                        <div class="border-t border-gray-100 dark:border-gray-600 pt-2 mt-2">
                                            <div class="text-sm font-bold text-gray-600 dark:text-gray-300 flex items-center justify-center">
                                                <i class="fa-solid fa-devices text-blue-600 dark:text-blue-400 mr-1"></i>
                                                {{ $plan->max_devices ?? 1 }}
                                                {{ ($plan->max_devices ?? 1) == 1 ? 'Device' : 'Devices' }}
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
                                            <button type="submit"
                                                class="w-full mt-3 bg-white text-blue-900 font-bold py-2 rounded-xl hover:bg-gray-100 transition-colors shadow-lg">Buy</button>
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
                                <tr
                                    class="text-gray-400 text-xs uppercase border-b border-gray-200 dark:border-gray-700">
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
                                        <td class="py-4 text-gray-600 dark:text-gray-300">
                                            {{ $txn->created_at->format('d M, h:i A') }}</td>
                                        <td class="py-4 font-bold text-gray-800 dark:text-white">
                                            {{ $txn->plan ? $txn->plan->name : 'Unknown Plan' }}</td>
                                        <td class="py-4 text-gray-600 dark:text-gray-300">₦{{ number_format($txn->amount) }}
                                        </td>
                                        <td class="py-4 text-xs font-mono text-gray-400 dark:text-gray-500">
                                            {{ Str::limit($txn->reference, 12) }}</td>
                                        <td class="py-4 text-gray-600 dark:text-gray-300">{{ strtoupper($txn->gateway) }}
                                        </td>
                                        <td class="py-4 text-right">
                                            @php
                                                [$txBadge, $txLabel] = match(strtolower($txn->status ?? 'success')) {
                                                    'completed', 'success' => ['bg-green-100 text-green-800 dark:bg-green-800/20 dark:text-green-400', 'Success'],
                                                    'pending'              => ['bg-yellow-100 text-yellow-800 dark:bg-yellow-800/20 dark:text-yellow-400', 'Pending'],
                                                    'failed'               => ['bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-400', 'Failed'],
                                                    default                => ['bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', ucfirst($txn->status ?? 'Unknown')],
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $txBadge }}">
                                                {{ $txLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">No transactions found yet.
                                        </td>
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

                {{-- Session History --}}
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-6">
                        <i class="fa-solid fa-clock-rotate-left mr-2 text-blue-500"></i>Session History
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-gray-400 text-xs uppercase border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-3 font-semibold">Device / MAC</th>
                                    <th class="pb-3 font-semibold">Started</th>
                                    <th class="pb-3 font-semibold">Duration</th>
                                    <th class="pb-3 font-semibold">Download</th>
                                    <th class="pb-3 font-semibold">Upload</th>
                                    <th class="pb-3 font-semibold">Router</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse($sessionHistory as $sess)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                            {{ $sess->callingstationid ? strtoupper($sess->callingstationid) : '—' }}
                                        </td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300 text-xs">
                                            {{ $sess->acctstarttime ? \Carbon\Carbon::parse($sess->acctstarttime)->format('d M, h:i A') : '—' }}
                                        </td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300 text-xs">
                                            @php
                                                $secs = is_numeric($sess->acctsessiontime) ? (int)$sess->acctsessiontime : 0;
                                                echo $secs > 0 ? \Carbon\CarbonInterval::seconds($secs)->cascade()->forHumans() : '—';
                                            @endphp
                                        </td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300 text-xs">
                                            {{ \Illuminate\Support\Number::fileSize((int)($sess->acctoutputoctets ?? 0)) }}
                                        </td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300 text-xs">
                                            {{ \Illuminate\Support\Number::fileSize((int)($sess->acctinputoctets ?? 0)) }}
                                        </td>
                                        <td class="py-3 text-gray-600 dark:text-gray-300 text-xs">
                                            {{ $sess->nasipaddress ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">No completed sessions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($sessionHistory->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $sessionHistory->links() }}
                        </div>
                    @endif
                </div>

            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl">
                    <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Quick Navigation</h4>
                    <div class="space-y-2">
                        @foreach([
                            ['href' => '#', 'icon' => 'fa-credit-card', 'label' => 'Subscription'],
                            ['href' => '#devices', 'icon' => 'fa-laptop', 'label' => 'My Devices'],
                            ['href' => '#hot-deals', 'icon' => 'fa-fire', 'label' => 'Hot Deals'],
                        ] as $nav)
                        <a href="{{ $nav['href'] }}" class="flex items-center gap-3 p-3 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors">
                            <i class="fa-solid {{ $nav['icon'] }} w-4 text-blue-500 text-center"></i>
                            {{ $nav['label'] }}
                            <i class="fa-solid fa-chevron-right ml-auto text-xs text-gray-400"></i>
                        </a>
                        @endforeach
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
                            <span
                                class="inline-flex items-center space-x-2 px-3 py-1 rounded-full text-xs font-bold {{ $isDeviceOnline ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                                @if($isDeviceOnline)
                                    <span class="relative inline-flex h-2 w-2">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-50"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                    </span>
                                @endif
                                <span>{{ $isDeviceOnline ? 'Online' : 'Offline' }}</span>
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
                            <span class="text-blue-600 dark:text-blue-400 font-bold text-right">
                                @if($isDeviceOnline)
                                    {{ ($currentSpeed && $currentSpeed !== '0 kbps') ? $currentSpeed : 'No limit' }}
                                @else
                                    Offline
                                @endif
                            </span>
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

                        @if($isDeviceOnline && $sessionDownload)
                        <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-gray-700 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-arrow-down text-white"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Downloaded</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">This Session</div>
                                </div>
                            </div>
                            <span class="text-green-600 dark:text-green-400 font-bold">{{ $sessionDownload }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-gray-700 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-arrow-up text-white"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">Uploaded</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">This Session</div>
                                </div>
                            </div>
                            <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $sessionUpload }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-900 to-blue-900 rounded-3xl p-6 shadow-xl">
                    <div class="text-center">
                        <div
                            class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-headset text-white text-3xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">Need Help?</h4>
                        <p class="text-white/80 text-sm mb-4">Our support team is available 24/7</p>
                        <a href="mailto:{{ config('mail.from.address', 'support@' . parse_url(config('app.url'), PHP_URL_HOST)) }}"
                            class="inline-block bg-white hover:bg-gray-100 text-gray-900 font-bold px-6 py-3 rounded-full transition-all duration-300 transform hover:scale-105">
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>


        </div>
    </div>

    {{-- Connecting overlay — shown instantly on click, navigation happens in background --}}
    <div id="connecting-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" aria-hidden="true">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-8 relative z-10 text-center">
            <div class="mb-5">
                <div class="w-16 h-16 mx-auto bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-wifi text-blue-600 dark:text-blue-400 text-2xl animate-pulse"></i>
                </div>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Connecting...</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Setting up your connection, please wait.</p>
            <div class="space-y-2 text-left">
                <div class="flex items-center gap-3 p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                    <i class="fa-solid fa-check-circle text-green-500 flex-shrink-0"></i>
                    Make sure you're connected to the WiFi network
                </div>
                <div class="flex items-center gap-3 p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                    <i class="fa-solid fa-check-circle text-green-500 flex-shrink-0"></i>
                    Turn off Mobile Data for best results
                </div>
                <div class="flex items-center gap-3 p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs text-gray-600 dark:text-gray-300">
                    <i class="fa-solid fa-spinner fa-spin text-blue-500 flex-shrink-0"></i>
                    Authenticating with router...
                </div>
            </div>
        </div>
    </div>

@once
<script>
    document.addEventListener('click', async function (e) {
        var btn = e.target.closest('[data-connect-btn]');
        if (!btn || btn.disabled) return;
        btn.disabled = true;

        var modal = document.getElementById('connecting-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        try {
            var token = document.querySelector('meta[name="csrf-token"]');
            var resp = await fetch("{{ route('dashboard.connect') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ? token.content : ''
                },
                body: JSON.stringify({})
            });

            var data = await resp.json().catch(function () { return {}; });

            if (resp.ok && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            btn.disabled = false;
            alert(data.message || 'Could not connect. Please try again.');
        } catch (err) {
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            btn.disabled = false;
            alert('Network error. Make sure you are connected to WiFi.');
        }
    });

    // Use event delegation on the document so this survives Livewire wire:poll re-renders.
    // This is intentionally outside the Livewire poll wrapper — it only runs once on page load.
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest('#disconnect-form');
        if (!form) return;

        e.preventDefault();

        if (!confirm('Disconnect this device from the network?')) return;

        const btn = form.querySelector('#disconnect-btn');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const mac   = form.querySelector('input[name="mac"]')?.value || null;

        if (btn) {
            btn.disabled = true;
            btn._origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Disconnecting...';
        }

        try {
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ mac }),
            });

            if (resp.ok) {
                const data = await resp.json().catch(() => ({}));

                // Livewire v4 uses $dispatch; fall back to v3 emit
                if (window.Livewire) {
                    if (typeof Livewire.dispatch === 'function') {
                        Livewire.dispatch('refreshDashboard');
                    } else if (typeof Livewire.emit === 'function') {
                        Livewire.emit('refreshDashboard');
                    }
                }

                // Navigate to the router logout URL to also close the captive portal session
                if (data?.logout_url) {
                    window.location.href = data.logout_url;
                }
            } else {
                form.submit();
            }
        } catch (err) {
            form.submit();
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btn._origHtml || '<i class="fa-solid fa-power-off mr-1"></i>Disconnect';
            }
        }
    });
</script>
@endonce