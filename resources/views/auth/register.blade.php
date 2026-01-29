<x-guest-layout>
    <!-- Register Heading -->
    <div class="text-center mb-8">
        <h2 class="text-4xl font-black text-transparent bg-clip-text bg-primary mb-3">
            Join HiFastLink
        </h2>
        <p class="text-gray-500">Create your account and get connected</p>
    </div>

    <!-- Registration Form -->
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Full Name Field -->
        <div class="group">
            <label for="name" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-user mr-2 text-primary"></i>Full Name
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <input 
                    id="name" 
                    type="text" 
                    name="name" 
                    value="{{ old('name') }}" 
                    required 
                    autofocus
                    autocomplete="name"
                    placeholder="Enter your full name"
                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('name') border-red-500 ring-4 ring-red-100 @enderror"
                >
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Username Field -->
        <div class="group">
            <label for="username" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-user-tag mr-2 text-primary"></i>Username
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-at"></i>
                </div>
                <input 
                    id="username" 
                    type="text" 
                    name="username" 
                    value="{{ old('username') }}" 
                    required
                    autocomplete="username"
                    placeholder="Choose a unique username"
                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('username') border-red-500 ring-4 ring-red-100 @enderror"
                >
            </div>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <!-- Email Field -->
        <div class="group">
            <label for="email" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-envelope mr-2 text-primary"></i>Email Address
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-at"></i>
                </div>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required
                    autocomplete="username"
                    placeholder="Enter your email"
                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('email') border-red-500 ring-4 ring-red-100 @enderror"
                >
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone Number Field -->
        <div class="group">
            <label for="phone" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-phone mr-2 text-primary"></i>Phone Number
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <input 
                    id="phone" 
                    type="tel" 
                    name="phone" 
                    value="{{ old('phone') }}" 
                    placeholder="Enter your phone number"
                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('phone') border-red-500 ring-4 ring-red-100 @enderror"
                >
            </div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password Field -->
        <div x-data="{ showPassword: false }" class="group">
            <label for="password" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-lock mr-2 text-primary"></i>Password
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-key"></i>
                </div>
                <input 
                    id="password" 
                    :type="showPassword ? 'text' : 'password'"
                    name="password" 
                    required
                    autocomplete="new-password"
                    placeholder="Create a strong password"
                    class="w-full pl-12 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('password') border-red-500 ring-4 ring-red-100 @enderror"
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

        <!-- Confirm Password Field -->
        <div x-data="{ showConfirmPassword: false }" class="group">
            <label for="password_confirmation" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-lock mr-2 text-primary"></i>Confirm Password
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <input 
                    id="password_confirmation" 
                    :type="showConfirmPassword ? 'text' : 'password'"
                    name="password_confirmation" 
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                    class="w-full pl-12 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('password_confirmation') border-red-500 ring-4 ring-red-100 @enderror"
                >
                <button 
                    type="button"
                    @click="showConfirmPassword = !showConfirmPassword"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 hover:bg-gray-100 rounded-lg transition-all duration-300"
                    tabindex="-1"
                >
                    <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-lg"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Register Button -->
        <button 
            type="submit"
            class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 group mt-6"
        >
            <span class="flex items-center justify-center">
                <i class="fa-solid fa-rocket mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                Create Account
            </span>
        </button>

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t-2 border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500 font-medium">Already have an account?</span>
            </div>
        </div>

        <!-- Login Link -->
        <a 
            href="{{ route('login') }}"
            class="block w-full text-center bg-white border-2 border-gray-300 hover:border-primary text-gray-700 hover:text-blue-700 font-semibold py-4 rounded-2xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg group"
        >
            <i class="fa-solid fa-right-to-bracket mr-2 group-hover:scale-110 inline-block transition-transform duration-300"></i>
            Sign In Instead
        </a>
    </form>
</x-guest-layout>
