<div>
    {{-- Header --}}
    <div class="text-center mb-8">
        <h2 class="text-3xl font-black text-transparent bg-clip-text bg-primary mb-2">
            @if($step === 'invoice')  Free Internet
            @elseif($step === 'phone')  Verify Your Number
            @elseif($step === 'otp')  Enter OTP
            @else  You're Connected!
            @endif
        </h2>
        <p class="text-gray-500 text-sm">
            @if($step === 'invoice')  Enter your BasmelCare receipt number to claim 1 free day of internet
            @elseif($step === 'phone')  We'll send a WhatsApp code to confirm it's you
            @elseif($step === 'otp')  Enter the 6-digit code sent to your WhatsApp
            @else  Your free internet access is now active
            @endif
        </p>
    </div>

    {{-- Alerts --}}
    @if($error)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> {{ $error }}
        </div>
    @endif

    @if($success && $step === 'otp')
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2">
            <i class="fa-brands fa-whatsapp"></i> {{ $success }}
        </div>
    @endif

    {{-- ── Step: invoice ── --}}
    @if($step === 'invoice')
        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-receipt mr-2 text-primary"></i>
                    Receipt / Invoice Number
                </label>
                <input
                    wire:model="invoiceNumber"
                    wire:keydown.enter="validateInvoice"
                    type="text"
                    placeholder="e.g. INV-20260705-0042"
                    autocomplete="off"
                    class="w-full px-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 text-sm focus:outline-none focus:border-primary focus:bg-white transition-all duration-300 uppercase"
                >
            </div>

            <button
                wire:click="validateInvoice"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:opacity-90 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 text-sm flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="validateInvoice">
                    <i class="fa-solid fa-check mr-1"></i> Redeem Receipt
                </span>
                <span wire:loading wire:target="validateInvoice">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Checking...
                </span>
            </button>
        </div>

    {{-- ── Step: phone ── --}}
    @elseif($step === 'phone')
        <div class="space-y-5">
            <div class="p-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                Receipt <strong>{{ $invoiceNumber }}</strong> verified!
                {{ $validityHours }}h of free internet ready to activate.
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-phone mr-2 text-primary"></i>
                    Your Phone Number
                </label>
                <input
                    wire:model="phone"
                    wire:keydown.enter="sendOtp"
                    type="tel"
                    placeholder="08012345678"
                    autocomplete="tel"
                    class="w-full px-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 text-sm focus:outline-none focus:border-primary focus:bg-white transition-all duration-300"
                >
            </div>

            <button
                wire:click="sendOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:opacity-90 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 text-sm flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="sendOtp">
                    <i class="fa-brands fa-whatsapp mr-1"></i> Send Code via WhatsApp
                </span>
                <span wire:loading wire:target="sendOtp">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Sending...
                </span>
            </button>

            <button wire:click="goBack" class="w-full text-gray-400 text-sm py-2 hover:text-gray-600">
                ← Change receipt number
            </button>
        </div>

    {{-- ── Step: otp ── --}}
    @elseif($step === 'otp')
        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                    <i class="fa-solid fa-key mr-2 text-primary"></i>
                    Verification Code
                </label>
                <input
                    wire:model="otp"
                    wire:keydown.enter="verifyOtp"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="• • • • • •"
                    autocomplete="one-time-code"
                    class="w-full px-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 text-center text-xl font-mono tracking-[0.5em] focus:outline-none focus:border-primary focus:bg-white transition-all duration-300"
                >
            </div>

            <button
                wire:click="verifyOtp"
                wire:loading.attr="disabled"
                class="w-full bg-primary hover:opacity-90 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 text-sm flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="verifyOtp">
                    <i class="fa-solid fa-wifi mr-1"></i> Activate Free Internet
                </span>
                <span wire:loading wire:target="verifyOtp">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Activating...
                </span>
            </button>

            <div class="flex justify-between text-sm text-gray-500">
                <button wire:click="goBack" class="hover:text-gray-700">← Back</button>
                <button wire:click="resendOtp" class="hover:text-primary">Resend code</button>
            </div>
        </div>

    {{-- ── Step: success (not on captive portal) ── --}}
    @elseif($step === 'success')
        <div class="text-center space-y-4">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <i class="fa-solid fa-wifi text-green-600 text-3xl"></i>
            </div>
            <div>
                <p class="font-bold text-gray-800 text-lg">Access Activated!</p>
                <p class="text-gray-500 text-sm mt-1">
                    Your free internet access is active until
                    <strong>{{ $expiresAt ? \Carbon\Carbon::parse($expiresAt)->format('D, d M Y h:i A') : 'tomorrow' }}</strong>.
                </p>
            </div>
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Connect to the HifastLink Wi-Fi network and your device will get online automatically.
                If not, open your browser — you should be connected.
            </div>
            <a href="{{ route('home') }}" class="block text-primary text-sm font-semibold mt-4">
                Go to homepage →
            </a>
        </div>
    @endif

    {{-- Powered-by note --}}
    <p class="text-center text-xs text-gray-400 mt-8">
        <i class="fa-solid fa-capsules mr-1"></i>
        Powered by BasmelCare Pharmacy × HifastLink
    </p>
</div>
