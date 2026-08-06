<div>
    {{-- Error --}}
    @if($error)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $error }}
        </div>
    @endif

    {{-- ── Step: phone ───────────────────────────────────────────── --}}
    @if($step === 'phone')
        <div class="space-y-5">
            <div class="group">
                <label for="otp-phone" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-phone mr-2 text-primary"></i>Phone Number
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <input
                        id="otp-phone"
                        type="tel"
                        wire:model.live="phone"
                        wire:keydown.enter="sendOtp"
                        autofocus
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="08012345678"
                        class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                </div>
            </div>

            <button
                wire:click="sendOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="sendOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-brands fa-whatsapp"></i> Send Code via WhatsApp
                </span>
                <span wire:loading wire:target="sendOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Sending...
                </span>
            </button>
        </div>

    {{-- ── Step: otp ──────────────────────────────────────────────── --}}
    @elseif($step === 'otp')
        @if($success)
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2">
                <i class="fa-brands fa-whatsapp"></i>
                {{ $success }}
            </div>
        @endif

        <div class="space-y-5">
            <div class="group">
                <label for="otp-code" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-key mr-2 text-primary"></i>Verification Code
                </label>
                <input
                    id="otp-code"
                    type="text"
                    wire:model.live="otp"
                    wire:keydown.enter="verifyOtp"
                    autofocus
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="6-digit code"
                    class="w-full text-center tracking-[0.5em] text-2xl font-bold py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                >
            </div>

            <button
                wire:click="verifyOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Verify
                </span>
                <span wire:loading wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Verifying...
                </span>
            </button>

            <div class="flex items-center justify-between text-sm">
                <button wire:click="back" class="text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <i class="fa-solid fa-arrow-left text-xs"></i> Change number
                </button>
                <button
                    wire:click="resendOtp"
                    wire:loading.attr="disabled"
                    class="text-primary hover:text-blue-700 font-medium disabled:opacity-40"
                    @if($resendCountdown > 0) disabled @endif
                >
                    @if($resendCountdown > 0)
                        Resend in {{ $resendCountdown }}s
                    @else
                        Resend code
                    @endif
                </button>
            </div>
        </div>

    {{-- ── Step: exists (account found, bonus blocked) ───────────── --}}
    @elseif($step === 'exists')
        <div class="space-y-5">
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                <span class="text-2xl leading-none mt-0.5">⚠️</span>
                <div>
                    <p class="text-sm font-bold text-amber-800">Account already exists</p>
                    <p class="text-sm text-amber-700 mt-1 leading-relaxed">
                        This number is linked to an existing account.
                        The free trial is only for new users — you didn't receive it.
                    </p>
                    <p class="text-sm text-amber-700 mt-2 leading-relaxed">
                        You can sign in and subscribe to a plan to get internet access.
                    </p>
                </div>
            </div>

            <button
                wire:click="loginExisting"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="loginExisting" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In to My Account
                </span>
                <span wire:loading wire:target="loginExisting" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Signing in...
                </span>
            </button>

            <button wire:click="back"
                class="w-full text-center text-sm text-gray-500 hover:text-gray-700 py-2 flex items-center justify-center gap-1">
                <i class="fa-solid fa-arrow-left text-xs"></i> Use a different number
            </button>
        </div>

    {{-- ── Step: password (new user — set password) ──────────────── --}}
    @elseif($step === 'password')
        @if($success)
            <div class="mb-5 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2">
                <i class="fa-brands fa-whatsapp"></i>
                {{ $success }}
            </div>
        @endif

        <div class="space-y-5">
            {{-- Password --}}
            <div x-data="{ show: false }" class="group">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-lock mr-2 text-primary"></i>Create a Password
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <input
                        :type="show ? 'text' : 'password'"
                        wire:model="password"
                        wire:keydown.enter="createAccount"
                        placeholder="Create a strong password"
                        class="w-full pl-12 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                    <button type="button" @click="show = !show" tabindex="-1"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 hover:bg-gray-100 rounded-lg transition-all duration-300">
                        <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-lg"></i>
                    </button>
                </div>
                @error('password')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Confirm Password --}}
            <div x-data="{ show: false }" class="group">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-lock mr-2 text-primary"></i>Confirm Password
                </label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <input
                        :type="show ? 'text' : 'password'"
                        wire:model="passwordConfirmation"
                        wire:keydown.enter="createAccount"
                        placeholder="Confirm your password"
                        class="w-full pl-12 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                    <button type="button" @click="show = !show" tabindex="-1"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none p-1 hover:bg-gray-100 rounded-lg transition-all duration-300">
                        <i :class="show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-lg"></i>
                    </button>
                </div>
                @error('passwordConfirmation')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
            </div>

            <button
                wire:click="createAccount"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="createAccount" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-rocket"></i>
                    {{ $bonus === 'free_trial' ? 'Create Account & Connect' : 'Create Account' }}
                </span>
                <span wire:loading wire:target="createAccount" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Creating account...
                </span>
            </button>

            <button wire:click="back"
                class="w-full text-center text-sm text-gray-500 hover:text-gray-700 py-2 flex items-center justify-center gap-1">
                <i class="fa-solid fa-arrow-left text-xs"></i> Use a different number
            </button>
        </div>
    @endif
</div>
