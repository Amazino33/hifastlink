<x-guest-layout>
    <!-- Login Heading -->
    <div class="text-center mb-8">
        <h2 class="text-4xl font-black text-transparent bg-clip-text bg-primary mb-3">
            Welcome Back
        </h2>
        <p class="text-gray-500">Sign in to continue your journey</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <!-- Magic link request (send link to email) -->
    <div class="text-center mb-4">
        <form method="POST" action="{{ route('router.send_link') }}" class="inline-block">
            @csrf
            <input type="email" name="email" placeholder="Email for magic link" class="px-3 py-2 rounded-md text-sm" required>
            <button type="submit" class="ml-2 px-3 py-2 bg-primary text-white rounded-md text-sm">Send login link</button>
        </form>
    </div>

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Login Field with Icon -->
        <div class="group">
            <label for="login" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-envelope mr-2 text-primary"></i>Email, Phone, or Username
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-user"></i>
                </div>
                <input 
                    id="login" 
                    type="text" 
                    name="login" 
                    value="{{ old('login') }}" 
                    required 
                    autofocus
                    autocomplete="username" 
                    placeholder="Enter your email, phone, or username"
                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('login') border-red-500 ring-4 ring-red-100 @enderror"
                >
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password Field with Toggle -->
        <div x-data="{ showPassword: false }" class="group">
            <label for="password" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-lock mr-2 text-primary"></i>Pin
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-primary transition-colors duration-300">
                    <i class="fa-solid fa-key"></i>
                </div>
                <input 
                    id="password" 
                    :type="showPassword ? 'text' : 'password'" 
                    name="password" 
                    required
                    autocomplete="current-password" 
                    placeholder="Enter your PIN"
                    class="w-full pl-12 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('password') border-red-500 ring-4 ring-red-100 @enderror"
                >
                <button 
                    type="button" 
                    @click="showPassword = !showPassword"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    tabindex="-1"
                >
                    <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-lg"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label class="flex items-center cursor-pointer group">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    name="remember"
                    class="w-5 h-5 text-blue-600 bg-gray-50 border-2 border-gray-300 rounded focus:ring-4 focus:ring-blue-100 transition-all duration-300 cursor-pointer"
                >
                <span class="ml-3 text-sm text-gray-600 group-hover:text-gray-900 transition-colors duration-300">
                    Remember me
                </span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline transition-all duration-300">
                    Forgot PIN?
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <button 
            type="submit"
            class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 group"
        >
            <span class="flex items-center justify-center">
                <i class="fa-solid fa-right-to-bracket mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                Log in
            </span>
        </button>

        <!-- Divider -->
        <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t-2 border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500 font-medium">New to HiFastLink?</span>
            </div>
        </div>

        <!-- Sign Up Link -->
        @if (Route::has('register'))
            <a 
                href="{{ route('register') }}"
                class="block w-full text-center bg-white border-2 border-gray-300 hover:border-primary text-gray-700 hover:text-primary font-semibold py-4 rounded-2xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg group"
            >
                <i class="fa-solid fa-user-plus mr-2 group-hover:scale-110 inline-block transition-transform duration-300"></i>
                Create Account
            </a>
        @endif
    </form>
</x-guest-layout>
