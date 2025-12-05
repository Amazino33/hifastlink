<x-guest-layout>
                    <!-- Register Heading -->
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-blue-600 mb-2">Sign Up</h1>
                        <p class="text-gray-500 text-sm">Create your account to get started.</p>
                    </div>

                    <!-- Registration Form -->
                    <form method="POST" action="{{ route('register') }}" class="space-y-5">
                        @csrf

                        <!-- Full Name Field -->
                        <div>
                            <label for="name" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Full Name
                            </label>
                            <input 
                                id="name" 
                                type="text" 
                                name="name" 
                                value="{{ old('name') }}" 
                                required 
                                autofocus
                                autocomplete="name"
                                placeholder="Enter your full name"
                                class="w-full px-4 py-4 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('name') ring-2 ring-red-500 @enderror"
                            >
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Email Address
                            </label>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required
                                autocomplete="username"
                                placeholder="Enter your email"
                                class="w-full px-4 py-4 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('email') ring-2 ring-red-500 @enderror"
                            >
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Phone Number Field (Optional - add to User model if needed) -->
                        <div>
                            <label for="phone" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Phone Number
                            </label>
                            <input 
                                id="phone" 
                                type="tel" 
                                name="phone" 
                                value="{{ old('phone') }}" 
                                placeholder="Enter your phone number"
                                class="w-full px-4 py-4 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('phone') ring-2 ring-red-500 @enderror"
                            >
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <!-- Password Field -->
                        <div x-data="{ showPassword: false }">
                            <label for="password" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Password
                            </label>
                            <div class="relative">
                                <input 
                                    id="password" 
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password" 
                                    required
                                    autocomplete="new-password"
                                    placeholder="Create a password"
                                    class="w-full px-4 py-4 pr-12 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('password') ring-2 ring-red-500 @enderror"
                                >
                                <button 
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    tabindex="-1"
                                >
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password Field -->
                        <div x-data="{ showConfirmPassword: false }">
                            <label for="password_confirmation" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <input 
                                    id="password_confirmation" 
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    name="password_confirmation" 
                                    required
                                    autocomplete="new-password"
                                    placeholder="Confirm your password"
                                    class="w-full px-4 py-4 pr-12 bg-gray-100 border-0 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-gray-50 focus:ring-2 focus:ring-blue-500 transition duration-200 @error('password_confirmation') ring-2 ring-red-500 @enderror"
                                >
                                <button 
                                    type="button"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    tabindex="-1"
                                >
                                    <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showConfirmPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <!-- Register Button -->
                        <button 
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Create Account
                        </button>

                        <!-- Footer Links -->
                        <div class="text-center pt-4">
                            <p class="text-sm text-gray-500">
                                Already have an account? 
                                <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">
                                    Login
                                </a>
                            </p>
                        </div>
                    </form>

                </x-guest-layout>