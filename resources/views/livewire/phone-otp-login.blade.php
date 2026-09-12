<div>
    {{-- Error --}}
    @if($error)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            <span>{{ $error }}</span>
        </div>
    @endif

    {{-- ── Step 1: Phone Number ───────────────────────────────────────────── --}}
    @if($step === 'phone')
        <div class="space-y-4">
            <div class="group">
                <label for="otp-phone" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                    <i class="fa-brands fa-whatsapp text-emerald-500 mr-1.5 text-sm"></i> WhatsApp Phone Number
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
                        placeholder="e.g. 08012345678"
                        class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                    >
                </div>
                <p class="text-xs text-gray-500 mt-1.5">
                    We'll send a 6-digit verification code to your WhatsApp.
                </p>
            </div>

            <button
                type="button"
                wire:click="sendOtp"
                wire:loading.attr="disabled"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-emerald-200 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="sendOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Send Code via WhatsApp
                </span>
                <span wire:loading wire:target="sendOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Sending code...
                </span>
            </button>
        </div>

    {{-- ── Step 2: 6-Digit OTP ─────────────────────────────────────────────── --}}
    @elseif($step === 'otp')
        @if($success)
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2">
                <i class="fa-brands fa-whatsapp text-emerald-600 text-base flex-shrink-0"></i>
                <span>{{ $success }}</span>
            </div>
        @endif

        {{-- Dev / Sandbox OTP helper --}}
        @if(! app()->isProduction() && session('dev_otp'))
            <div class="mb-4 p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center justify-between">
                <span>⚡ Sandbox OTP: <strong>{{ session('dev_otp') }}</strong></span>
                <button type="button" wire:click="$set('otp', '{{ session('dev_otp') }}')" class="text-xs text-blue-600 font-semibold underline">
                    Auto-fill
                </button>
            </div>
        @endif

        <div class="space-y-4">
            <div class="group">
                <label for="otp-code" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-shield-halved text-primary mr-1.5"></i> Enter 6-Digit Code
                </label>
                <input
                    id="otp-code"
                    type="text"
                    wire:model.live="otp"
                    wire:keydown.enter="verifyOtp"
                    autofocus
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="• • • • • •"
                    class="w-full text-center tracking-[0.4em] text-2xl font-bold py-3.5 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300"
                >
            </div>

            <button
                type="button"
                wire:click="verifyOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-3.5 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:transform-none"
            >
                <span wire:loading.remove wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-bolt"></i> Verify & Connect
                </span>
                <span wire:loading wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i> Verifying...
                </span>
            </button>

            <div class="flex items-center justify-between text-xs pt-1">
                <button type="button" wire:click="back" class="text-gray-500 hover:text-gray-700 flex items-center gap-1 font-medium">
                    <i class="fa-solid fa-arrow-left"></i> Change number
                </button>
                <button
                    type="button"
                    wire:click="resendOtp"
                    wire:loading.attr="disabled"
                    class="text-primary hover:text-blue-700 font-semibold disabled:opacity-40"
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
    @endif
</div>
