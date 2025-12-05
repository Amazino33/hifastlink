<x-guest-layout>
                    <!-- Login Heading -->
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-blue-600 mb-2">Login</h1>
                        <p class="text-gray-500 text-sm">Sign in to continue.</p>
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-6" :status="session('status')" />

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        <!-- Email/Username Field -->
                        <div>
                            <label for="email"
                                class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Username
                            </label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                autocomplete="username" placeholder="Enter your username"
                                class="w-full px-4 py-4 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('email') ring-2 ring-red-500 @enderror">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password Field -->
                        <div x-data="{ showPassword: false }">
                            <label for="password"
                                class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Pin
                            </label>
                            <div class="relative">
                                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                    autocomplete="current-password" placeholder="Enter your PIN"
                                    class="w-full px-4 py-4 pr-12 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('password') ring-2 ring-red-500 @enderror">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    tabindex="-1">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center">
                            <input id="remember_me" type="checkbox" name="remember"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                            <label for="remember_me" class="ml-2 text-sm text-gray-600">
                                Remember me
                            </label>
                        </div>

                        <!-- Login Button -->
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Log in
                        </button>

                        <!-- Footer Links -->
                        <div class="text-center space-y-2 pt-4">
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                    class="block text-sm text-gray-500 hover:text-blue-600 transition">
                                    Forgot Password?
                                </a>
                            @endif

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                    class="block text-sm text-gray-500 hover:text-blue-600 transition">
                                    Signup !
                                </a>
                            @endif
                        </div>
                    </form>
</x-guest-layout>