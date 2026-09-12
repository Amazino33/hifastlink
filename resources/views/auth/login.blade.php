{{-- resources/views/auth/login.blade.php --}}

<x-guest-layout>

@php
    $freeWifiEnabled = \App\Models\AppSetting::bool('free_wifi_enabled', false);
    $isFreeWifi      = $freeWifiEnabled || request('bonus') === 'free_trial';
    $freeWifiInstruction = \App\Models\AppSetting::get('free_wifi_instruction') ?: 'Verify your WhatsApp number or continue with Google — free internet access is applied automatically.';

    $linkLogin = request()->get('link-login')
              ?? request()->get('link-login-only')
              ?? request()->get('link_login')
              ?? request()->get('link-orig');

    $mac    = request()->get('mac');
    $ip     = request()->get('ip');
    $router = request()->get('router');

    $googleUrl = route('auth.google', array_filter([
        'bonus'      => request('bonus'),
        'router'     => $router,
        'link-login' => $linkLogin,
        'mac'        => $mac,
        'ip'         => $ip,
    ]));

    // Determine default tab:
    // If request('tab') is passed, use that.
    // If voucher query is present, use 'voucher'.
    // Otherwise default to 'whatsapp'.
    $defaultTab = request('tab') ?: (request()->has('voucher') ? 'voucher' : 'whatsapp');
@endphp

<div x-data="{
    tab: '{{ $defaultTab }}',
}">

    <div class="text-center mb-6">
        <h2 class="text-3xl font-black text-transparent bg-clip-text bg-primary mb-2">
            {{ $linkLogin ? 'Get Connected' : 'Welcome to HiFastLink' }}
        </h2>
        <p class="text-gray-500 text-sm">
            {{ $linkLogin ? 'Instant high-speed Wi-Fi access' : 'Sign in or join with WhatsApp or Google' }}
        </p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if(session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation text-red-500 text-lg flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($isFreeWifi)
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-4 mb-5 flex items-start gap-3">
            <span class="text-2xl leading-none">🎁</span>
            <div>
                <p class="text-sm font-bold text-blue-900">Free Wi-Fi Available</p>
                <p class="text-xs text-blue-700 mt-0.5">{{ $freeWifiInstruction }}</p>
            </div>
        </div>
    @endif

    {{-- Google One-Tap Sign In / Sign Up --}}
    <a href="{{ $googleUrl }}"
        class="flex items-center justify-center gap-3 w-full border-2 border-gray-200 hover:border-blue-400 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-3.5 rounded-2xl transition-all duration-300 shadow-sm hover:shadow mb-5 group">
        <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        <span>Continue with Google</span>
    </a>

    {{-- Divider --}}
    <div class="relative my-5">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center text-xs text-gray-400">
            <span class="bg-white px-3 font-medium">or continue with</span>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex rounded-2xl bg-gray-100 p-1 mb-5">
        <button type="button" @click="tab = 'whatsapp'"
            :class="tab === 'whatsapp' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            class="flex-1 py-2.5 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-200">
            <i class="fa-brands fa-whatsapp text-emerald-500 mr-1"></i> WhatsApp
        </button>

        <button type="button" @click="tab = 'voucher'"
            :class="tab === 'voucher' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            class="flex-1 py-2.5 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-200">
            <i class="fa-solid fa-ticket text-amber-500 mr-1"></i> Voucher
        </button>

        <button type="button" @click="tab = 'pin'"
            :class="tab === 'pin' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
            class="flex-1 py-2.5 text-xs sm:text-sm font-semibold rounded-xl transition-all duration-200">
            <i class="fa-solid fa-key text-blue-500 mr-1"></i> PIN / Pass
        </button>
    </div>

    {{-- ══════════════════════════════════
         TAB 1: WHATSAPP OTP
    ══════════════════════════════════ --}}
    <div x-show="tab === 'whatsapp'" x-cloak>
        @livewire('phone-otp-login', [
            'mode'      => 'login',
            'bonus'     => request('bonus', ''),
            'router'    => $router ?? '',
            'linkLogin' => $linkLogin ?? '',
            'mac'       => $mac ?? '',
            'ip'        => $ip ?? '',
        ], key('phone-otp-login'))
    </div>

    {{-- ══════════════════════════════════
         TAB 2: VOUCHER CODE
    ══════════════════════════════════ --}}
    <div x-show="tab === 'voucher'" x-cloak>
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            @if($linkLogin)
                <input type="hidden" name="link_login" value="{{ $linkLogin }}">
            @endif
            @if($mac)
                <input type="hidden" name="mac" value="{{ $mac }}">
            @endif
            @if($ip)
                <input type="hidden" name="ip" value="{{ $ip }}">
            @endif
            @if($router)
                <input type="hidden" name="router" value="{{ $router }}">
            @endif

            <div class="group">
                <label for="voucher-code" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-ticket text-amber-500 mr-1.5"></i> Voucher Code
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <input
                        id="voucher-code"
                        type="text"
                        name="login"
                        required
                        autocapitalize="characters"
                        placeholder="VCH-XXXXX"
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 uppercase font-mono tracking-wider placeholder-gray-400 focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-100 transition-all duration-300"
                    >
                </div>
                <p class="text-xs text-gray-500 mt-1.5">Enter the voucher code printed on your card.</p>
            </div>

            <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3.5 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-amber-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-bolt"></i> Connect with Voucher
                </span>
            </button>
        </form>
    </div>

    {{-- ══════════════════════════════════
         TAB 3: PIN / PASSWORD LOGIN
    ══════════════════════════════════ --}}
    <div x-show="tab === 'pin'" x-cloak>
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            @if($linkLogin)
                <input type="hidden" name="link_login" value="{{ $linkLogin }}">
            @endif
            @if($mac)
                <input type="hidden" name="mac" value="{{ $mac }}">
            @endif
            @if($ip)
                <input type="hidden" name="ip" value="{{ $ip }}">
            @endif
            @if($router)
                <input type="hidden" name="router" value="{{ $router }}">
            @endif

            <div class="group">
                <label for="login-account" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-user text-primary mr-1.5"></i> Phone, Username, or Email
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <input
                        id="login-account"
                        type="text"
                        name="login"
                        value="{{ old('login') }}"
                        required
                        autofocus
                        placeholder="e.g. 08012345678"
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                </div>
            </div>

            <div x-data="{ show: false }" class="group">
                <div class="flex items-center justify-between mb-2">
                    <label for="login-password" class="block text-xs font-bold text-gray-600 uppercase tracking-wider">
                        <i class="fa-solid fa-lock text-primary mr-1.5"></i> PIN / Password
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs text-primary hover:underline font-medium">
                        Forgot?
                    </a>
                </div>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <input
                        id="login-password"
                        :type="show ? 'text' : 'password'"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full pl-12 pr-12 py-3.5 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                    <button type="button" @click="show = !show" tabindex="-1"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1">
                        <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-3.5 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-200">
                <span class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In & Connect
                </span>
            </button>
        </form>
    </div>

    {{-- Bottom helper note --}}
    <div class="mt-6 pt-4 border-t border-gray-100 text-center">
        <p class="text-xs text-gray-500">
            No account yet? Use <button type="button" @click="tab = 'whatsapp'" class="text-emerald-600 font-bold hover:underline">WhatsApp</button> or <a href="{{ $googleUrl }}" class="text-blue-600 font-bold hover:underline">Google</a> above for instant setup.
        </p>
    </div>

</div>

</x-guest-layout>
