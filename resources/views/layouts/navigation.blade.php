<nav x-data="{ open: false }" class="bg-nav dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 shadow-xl relative z-50">
    <!-- Animated background effect -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-blue-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    <!-- Primary Navigation Menu -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="flex items-center group">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 transform hover:scale-105 transition-all duration-300">
                        <x-application-logo class="block h-10 w-auto fill-current text-white drop-shadow-lg" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" 
                        class="relative px-6 py-3 text-white font-semibold hover:text-blue-300 transition-all duration-300 group">
                        <span class="relative z-10">{{ __('Dashboard') }}</span>
                        <span class="absolute inset-0 bg-white/10 rounded-lg scale-0 group-hover:scale-100 transition-transform duration-300"></span>
                    </x-nav-link>
                    
                    <a href="#" class="relative px-6 py-3 text-white font-semibold hover:text-blue-300 transition-all duration-300 group">
                        <span class="relative z-10">Services</span>
                        <span class="absolute inset-0 bg-white/10 rounded-lg scale-0 group-hover:scale-100 transition-transform duration-300"></span>
                    </a>
                    
                    <a href="#" class="relative px-6 py-3 text-white font-semibold hover:text-blue-300 transition-all duration-300 group">
                        <span class="relative z-10">Pricing</span>
                        <span class="absolute inset-0 bg-white/10 rounded-lg scale-0 group-hover:scale-100 transition-transform duration-300"></span>
                    </a>
                    
                    <a href="#" class="relative px-6 py-3 text-white font-semibold hover:text-blue-300 transition-all duration-300 group">
                        <span class="relative z-10">Contact</span>
                        <span class="absolute inset-0 bg-white/10 rounded-lg scale-0 group-hover:scale-100 transition-transform duration-300"></span>
                    </a>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:space-x-4">
                <!-- Login Button -->
                @guest
                <a href="{{ route('login') }}" class="bg-white/20 hover:bg-white/30 text-white font-bold px-6 py-3 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                    Log In
                </a>
                <!-- CTA Button -->
                <a href="#" class="bg-blue-200 text-gray-900 font-bold px-6 py-3 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                    Get Started
                </a>
                @endguest

                <!-- User Dropdown -->
                @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-4 py-3 border-2 border-white/30 text-sm leading-4 font-semibold rounded-full text-white bg-white/10 backdrop-blur-sm hover:bg-white/20 hover:border-white/50 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center text-gray-900 font-bold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <span>{{ Auth::user()->name }}</span>
                            </div>

                            <div class="ms-2">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="py-2">
                            <x-dropdown-link :href="route('profile.edit')" class="flex items-center space-x-2 hover:bg-blue-50">
                                <i class="fa-solid fa-user text-blue-600"></i>
                                <span>{{ __('Profile') }}</span>
                            </x-dropdown-link>

                            <x-dropdown-link href="#" class="flex items-center space-x-2 hover:bg-blue-50">
                                <i class="fa-solid fa-gear text-blue-600"></i>
                                <span>Settings</span>
                            </x-dropdown-link>

                            <div class="border-t border-gray-200 my-2"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="flex items-center space-x-2 hover:bg-red-50 text-red-600">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>{{ __('Log Out') }}</span>
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-3 rounded-lg text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-300">
                    <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/10 backdrop-blur-md border-t border-white/20">
        <div class="pt-4 pb-3 space-y-2 px-4">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" 
                class="text-white hover:bg-white/20 rounded-lg font-semibold flex items-center space-x-2 py-3 px-4">
                <i class="fa-solid fa-dashboard"></i>
                <span>{{ __('Dashboard') }}</span>
            </x-responsive-nav-link>

            <a href="#" class="text-white hover:bg-white/20 rounded-lg font-semibold flex items-center space-x-2 py-3 px-4 transition-all duration-300">
                <i class="fa-solid fa-server"></i>
                <span>Services</span>
            </a>

            <a href="#" class="text-white hover:bg-white/20 rounded-lg font-semibold flex items-center space-x-2 py-3 px-4 transition-all duration-300">
                <i class="fa-solid fa-tag"></i>
                <span>Pricing</span>
            </a>

            <a href="#" class="text-white hover:bg-white/20 rounded-lg font-semibold flex items-center space-x-2 py-3 px-4 transition-all duration-300">
                <i class="fa-solid fa-envelope"></i>
                <span>Contact</span>
            </a>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-4 border-t border-white/20">
            @auth
            <div class="px-4 mb-4">
                <div class="flex items-center space-x-3 bg-white/10 rounded-lg p-3">
                    <div class="w-12 h-12 bg-blue-400 rounded-full flex items-center justify-center text-gray-900 font-bold text-xl">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-white">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-blue-100">{{ Auth::user()->email }}</div>
                    </div>
                </div>
            </div>

            <div class="space-y-2 px-4">
                <x-responsive-nav-link :href="route('profile.edit')" 
                    class="text-white hover:bg-white/20 rounded-lg font-semibold flex items-center space-x-2 py-3 px-4">
                    <i class="fa-solid fa-user"></i>
                    <span>{{ __('Profile') }}</span>
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-red-300 hover:bg-red-500/20 rounded-lg font-semibold flex items-center space-x-2 py-3 px-4">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>{{ __('Log Out') }}</span>
                    </x-responsive-nav-link>
                </form>
            </div>
            @endauth

            @guest
            <!-- Mobile CTA -->
            <div class="px-4 mt-4">
                <a href="{{ route('login') }}" class="block text-center bg-white/20 hover:bg-white/30 text-white font-bold px-6 py-4 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg">
                    Log In
                </a>
            </div>

            <div class="px-4 mt-4">
                <a href="{{ route('register') }}" class="block text-center bg-blue-400 hover:bg-blue-300 text-gray-900 font-bold px-6 py-4 rounded-full transform hover:scale-105 transition-all duration-300 shadow-lg">
                    Get Started
                </a>
            </div>
            @endguest
        </div>
    </div>
</nav>