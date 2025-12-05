{{-- resources/views/auth/register.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Register</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4 py-12">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                
                <!-- Blue Header Section with Logo -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 px-6 pt-12 pb-20 relative">
                    <!-- Network Pattern Background -->
                    <div class="absolute inset-0 opacity-10">
                        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                            </pattern>
                            <rect width="100" height="100" fill="url(#grid)" />
                        </svg>
                    </div>
                    
                    <!-- Logo -->
                    <div class="flex justify-center mb-6 relative z-10">
                        <div class="w-20 h-20 bg-white rounded-2xl shadow-lg flex items-center justify-center">
                            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- White Form Section with Curved Top -->
                <div class="bg-white -mt-12 rounded-t-[3rem] relative z-10 px-8 pt-10 pb-8">
                    
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

                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>