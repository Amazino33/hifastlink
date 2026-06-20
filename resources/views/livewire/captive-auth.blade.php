<div>
    {{-- Header --}}
    <div class="text-center mb-8">
        <h2 class="text-3xl font-black text-transparent bg-clip-text bg-primary mb-2">
            @if($step === 'phone')
                Get Connected
            @elseif($step === 'otp')
                Verify Your Number
            @else
                Connecting...
            @endif
        </h2>
        <p class="text-gray-500 text-sm">
            @if($step === 'phone')
                Enter your phone number to get started
            @elseif($step === 'otp')
                Enter the code sent to your WhatsApp
            @endif
        </p>
    </div>

    {{-- Error / Success messages --}}
    @if($error)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $error }}
        </div>
    @endif

    @if($success && $step === 'otp')
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2">
            <i class="fa-brands fa-whatsapp"></i>
            {{ $success }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════
         STEP 1: Phone number input
         ══════════════════════════════════════════════════════════════ --}}
    @if($step === 'phone')
        <div class="space-y-5">
            {{-- Phone input --}}
            <div class="group">
                <label for="phone" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-phone mr-2 text-primary"></i>
                    Phone Number or Voucher Code
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-primary transition-colors duration-300">
                        @if($isVoucher)
                            <i class="fa-solid fa-ticket"></i>
                        @else
                            <i class="fa-solid fa-phone"></i>
                        @endif
                    </div>
                    <input
                        id="phone"
                        type="text"
                        wire:model.live.debounce.300ms="phone"
                        wire:keydown.enter="sendOtp"
                        autofocus
                        autocomplete="tel"
                        inputmode="{{ $isVoucher ? 'text' : 'tel' }}"
                        placeholder="08012345678 or VCH-XXXXXXXX"
                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                </div>

                @if($isVoucher)
                    <div class="mt-2 flex items-center gap-2 text-green-600 text-sm font-medium">
                        <i class="fa-solid fa-ticket"></i>
                        Voucher detected — tap Connect
                    </div>
                @endif
            </div>

            {{-- Submit --}}
            <button
                wire:click="sendOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="sendOtp" class="flex items-center justify-center gap-2">
                    @if($isVoucher)
                        <i class="fa-solid fa-wifi"></i> Connect
                    @else
                        <i class="fa-brands fa-whatsapp"></i> Send Code via WhatsApp
                    @endif
                </span>
                <span wire:loading wire:target="sendOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Sending...
                </span>
            </button>

            {{-- Divider --}}
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t-2 border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500 font-medium">Or</span>
                </div>
            </div>

            {{-- Google OAuth --}}
            <a href="{{ route('auth.google') }}"
                class="flex items-center justify-center gap-3 w-full border-2 border-gray-300 hover:border-blue-400 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-4 rounded-2xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg">
                <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Continue with Google
            </a>
        </div>

    {{-- ══════════════════════════════════════════════════════════════
         STEP 2: OTP verification
         ══════════════════════════════════════════════════════════════ --}}
    @elseif($step === 'otp')
        <div class="space-y-5">
            {{-- Back button + phone display --}}
            <div class="flex items-center gap-3 mb-2">
                <button wire:click="goBack" class="text-gray-400 hover:text-gray-700 transition-colors p-1">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </button>
                <div class="text-sm text-gray-600">
                    Code sent to <span class="font-semibold text-gray-800">{{ $phone }}</span>
                </div>
            </div>

            {{-- OTP input --}}
            <div class="group">
                <label for="otp" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-shield-halved mr-2 text-primary"></i>
                    Verification Code
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-primary transition-colors duration-300">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <input
                        id="otp"
                        type="text"
                        wire:model="otp"
                        wire:keydown.enter="verifyOtp"
                        autofocus
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]*"
                        placeholder="Enter 6-digit code"
                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 text-center text-2xl tracking-[0.5em] placeholder:text-base placeholder:tracking-normal font-mono focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                </div>
            </div>

            {{-- Verify button --}}
            <button
                wire:click="verifyOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check-circle"></i> Verify & Connect
                </span>
                <span wire:loading wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Verifying...
                </span>
            </button>

            {{-- Resend --}}
            <div class="text-center text-sm text-gray-500" x-data="{ countdown: @entangle('resendCountdown'), _timer: null }" x-init="
                _timer = setInterval(() => { if (countdown > 0) countdown--; }, 1000);
            " x-on:remove.window="clearInterval(_timer)">
                <template x-if="countdown > 0">
                    <p>Resend code in <span class="font-semibold text-gray-700" x-text="countdown"></span>s</p>
                </template>
                <template x-if="countdown <= 0">
                    <button wire:click="resendOtp" class="text-primary hover:underline font-medium">
                        <i class="fa-brands fa-whatsapp mr-1"></i> Resend Code
                    </button>
                </template>
            </div>
        </div>

    {{-- ══════════════════════════════════════════════════════════════
         STEP 3: Bridge to MikroTik (auto-submit)
         ══════════════════════════════════════════════════════════════ --}}
    @elseif($step === 'done' && $bridgeLinkLogin)
        <div class="text-center py-8">
            <div class="mb-4">
                <i class="fa-solid fa-wifi text-5xl text-primary animate-pulse"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Connecting you to WiFi...</h3>
            <p class="text-gray-500 text-sm">Please wait while we set up your connection.</p>
        </div>

        <script>
            (function () {
                const linkLogin = @js($bridgeLinkLogin);
                const username  = @js($bridgeUsername);
                const password  = @js($bridgePassword);
                const linkOrig  = @js($bridgeLinkOrig);

                const separator = linkLogin.includes('?') ? '&' : '?';
                const url = linkLogin + separator
                    + 'username=' + encodeURIComponent(username)
                    + '&password=' + encodeURIComponent(password)
                    + '&dst=' + encodeURIComponent(linkOrig);

                setTimeout(function () {
                    window.location.href = url;
                }, 500);
            })();
        </script>
    @endif
</div>
