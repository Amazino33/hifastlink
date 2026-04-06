@php
    $isAffiliate  = auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('affiliate');
    $isAdmin      = auth()->check() && (
                        auth()->user()->hasRole('super_admin') ||
                        auth()->user()->hasRole('cashier')     ||
                        auth()->user()->email === 'amazino33@gmail.com'
                    );
    $isFamilyHead = auth()->check() && auth()->user()->is_family_admin;

    $navLinks = [
        ['route' => 'dashboard', 'label' => 'Dashboard',  'icon' => 'fa-gauge-high'],
        ['route' => 'services',  'label' => 'Services',   'icon' => 'fa-server'],
        ['route' => 'pricing',   'label' => 'Pricing',    'icon' => 'fa-tag'],
        ['route' => 'about',     'label' => 'About Us',   'icon' => 'fa-circle-info'],
        ['route' => 'contact',   'label' => 'Contact',    'icon' => 'fa-envelope'],
    ];
@endphp

<nav x-data="{ open: false }" class="bg-nav shadow-xl relative z-50">

    {{-- Decorative background blobs --}}
    <div class="absolute inset-0 opacity-10 pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-blue-300 rounded-full blur-3xl animate-pulse" style="animation-delay:1s"></div>
    </div>

    {{-- ================================================================
         DESKTOP NAV
    ================================================================ --}}
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">

            {{-- Logo + primary links --}}
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center transform hover:scale-105 transition-all duration-300">
                    <x-application-logo class="block h-10 w-auto fill-current text-white drop-shadow-lg" />
                </a>

                <div class="hidden sm:flex sm:items-center sm:ms-10 space-x-1">
                    @foreach ($navLinks as $link)
                        <a href="{{ route($link['route']) }}"
                            class="relative px-5 py-3 text-sm text-white font-semibold rounded-lg transition-all duration-300 hover:text-blue-200 group
                                   {{ request()->routeIs($link['route']) ? 'bg-white/10' : '' }}">
                            {{ $link['label'] }}
                            <span class="absolute inset-0 bg-white/10 rounded-lg scale-0 group-hover:scale-100 transition-transform duration-300"></span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Right side --}}
            <div class="hidden sm:flex sm:items-center sm:space-x-3">
                @guest
                    <a href="{{ route('login') }}"
                        class="bg-white/20 hover:bg-white/30 text-white text-sm font-bold px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 shadow-md">
                        Log In
                    </a>
                    <a href="{{ route('register') }}"
                        class="bg-blue-200 text-gray-900 text-sm font-bold px-5 py-2.5 rounded-full transition-all duration-300 hover:scale-105 shadow-md">
                        Get Started
                    </a>
                @endguest

                @auth
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 px-4 py-2.5 border-2 border-white/30 text-sm font-semibold rounded-full text-white bg-white/10 backdrop-blur-sm hover:bg-white/20 hover:border-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300 hover:scale-105 shadow-lg">
                                <span class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center text-gray-900 font-bold text-sm">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="py-1">
                                {{-- Standard links --}}
                                <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-2 hover:bg-blue-50">
                                    <i class="fa-solid fa-user w-4 text-blue-600"></i>
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <x-dropdown-link href="#" class="flex items-center gap-2 hover:bg-blue-50">
                                    <i class="fa-solid fa-gear w-4 text-blue-600"></i>
                                    Settings
                                </x-dropdown-link>

                                {{-- Family head --}}
                                @if ($isFamilyHead)
                                    <x-dropdown-link :href="route('vouchers.index')" class="flex items-center gap-2 hover:bg-blue-50">
                                        <i class="fa-solid fa-ticket w-4 text-blue-600"></i>
                                        {{ __('Family Vouchers') }}
                                    </x-dropdown-link>
                                @endif

                                {{-- Affiliate --}}
                                @if ($isAffiliate)
                                    <x-dropdown-link :href="route('request-custom-plans')" class="flex items-center gap-2 hover:bg-green-50">
                                        <i class="fa-solid fa-circle-plus w-4 text-green-600"></i>
                                        Request Custom Plan
                                    </x-dropdown-link>
                                @endif

                                @if ($isAffiliate && Auth::user()->router_id)
                                    <x-dropdown-link :href="route('affiliate.router.analytics')" class="flex items-center gap-2 hover:bg-blue-50">
                                        <i class="fa-solid fa-chart-line w-4 text-blue-600"></i>
                                        Router Analytics
                                    </x-dropdown-link>
                                @endif

                                {{-- Admin --}}
                                @if ($isAdmin)
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <x-dropdown-link href="/admin" class="flex items-center gap-2 hover:bg-purple-50 bg-purple-50/50">
                                        <i class="fa-solid fa-user-shield w-4 text-purple-600"></i>
                                        <span class="font-bold text-purple-700">Admin Panel</span>
                                    </x-dropdown-link>
                                @endif

                                <div class="border-t border-gray-100 my-1"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="flex items-center gap-2 hover:bg-red-50 text-red-600">
                                        <i class="fa-solid fa-right-from-bracket w-4"></i>
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            {{-- Hamburger --}}
            <div class="flex items-center sm:hidden">
                <button @click="open = !open"
                    class="p-2.5 rounded-lg text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300"
                    :aria-expanded="open" aria-label="Toggle navigation">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open }"       stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open }"  class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         MOBILE NAV
    ================================================================ --}}
    <div x-show="open" x-transition
        class="sm:hidden bg-white/10 backdrop-blur-md border-t border-white/20">

        {{-- Page links --}}
        <div class="px-4 pt-4 pb-2 space-y-1">
            @foreach ($navLinks as $link)
                <a href="{{ route($link['route']) }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-white font-semibold transition-all duration-300 hover:bg-white/20
                           {{ request()->routeIs($link['route']) ? 'bg-white/20' : '' }}">
                    <i class="fa-solid {{ $link['icon'] }} w-4"></i>
                    {{ $link['label'] }}
                </a>
            @endforeach

            @if ($isAdmin)
                <a href="/admin"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg font-bold bg-purple-600/20 border-2 border-purple-400/50 text-purple-200 hover:bg-purple-500/30 transition-all duration-300">
                    <i class="fa-solid fa-user-shield w-4"></i>
                    Admin Panel
                </a>
            @endif
        </div>

        {{-- User section --}}
        <div class="border-t border-white/20 px-4 pt-4 pb-6">
            @auth
                {{-- User card --}}
                <div class="flex items-center gap-3 bg-white/10 rounded-lg p-3 mb-4">
                    <div class="w-11 h-11 bg-blue-400 rounded-full flex items-center justify-center text-gray-900 font-bold text-lg flex-shrink-0">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-white truncate">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-blue-100 truncate">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-white/20">
                        <i class="fa-solid fa-user w-4"></i>
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    @if ($isFamilyHead)
                        <x-responsive-nav-link :href="route('vouchers.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-white/20">
                            <i class="fa-solid fa-ticket w-4"></i>
                            {{ __('Family Vouchers') }}
                        </x-responsive-nav-link>
                    @endif

                    @if ($isAffiliate)
                        <x-responsive-nav-link :href="route('request-custom-plans')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-green-500/30 bg-green-600/20">
                            <i class="fa-solid fa-circle-plus w-4"></i>
                            Request Custom Plan
                        </x-responsive-nav-link>
                    @endif

                    @if ($isAffiliate && Auth::user()->router_id)
                        <x-responsive-nav-link :href="route('affiliate.router.analytics')" class="flex items-center gap-3 px-4 py-3 rounded-lg text-white hover:bg-blue-500/30 bg-blue-600/20">
                            <i class="fa-solid fa-chart-line w-4"></i>
                            Router Analytics
                        </x-responsive-nav-link>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-300 hover:bg-red-500/20">
                            <i class="fa-solid fa-right-from-bracket w-4"></i>
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            @endauth

            @guest
                <div class="space-y-3 mt-2">
                    <a href="{{ route('login') }}"
                        class="block text-center bg-white/20 hover:bg-white/30 text-white font-bold px-6 py-3.5 rounded-full transition-all duration-300 hover:scale-105 shadow-md">
                        Log In
                    </a>
                    <a href="{{ route('register') }}"
                        class="block text-center bg-blue-400 hover:bg-blue-300 text-gray-900 font-bold px-6 py-3.5 rounded-full transition-all duration-300 hover:scale-105 shadow-md">
                        Get Started
                    </a>
                </div>
            @endguest
        </div>
    </div>

</nav>