<div>
    {{-- Header --}}
    <div class="text-center mb-8">
        <h2 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-violet-600 to-purple-600 mb-2">
            @if($step === 'code')  Free Internet
            @else  You're Connected!
            @endif
        </h2>
        <p class="text-gray-500 text-sm">
            @if($step === 'code')  Enter the Wi-Fi code printed on your receipt
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

    {{-- ── Step: code ── --}}
    @if($step === 'code')
        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                    <i class="fa-solid fa-gamepad mr-2 text-violet-600"></i>
                    Wi-Fi Code
                </label>
                <p class="text-xs text-gray-400 mb-3">The code printed in the Wi-Fi Access box on your receipt</p>
                <input
                    wire:model="code"
                    wire:keydown.enter="validateCode"
                    type="text"
                    placeholder="e.g. A3BK7NMQ"
                    autocomplete="off"
                    maxlength="12"
                    class="w-full px-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 text-sm focus:outline-none focus:border-violet-500 focus:bg-white transition-all duration-300 uppercase tracking-widest"
                >
            </div>

            <button
                wire:click="validateCode"
                wire:loading.attr="disabled"
                class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 text-sm flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="validateCode">
                    <i class="fa-solid fa-wifi mr-1"></i> Connect to Wi-Fi
                </span>
                <span wire:loading wire:target="validateCode">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Connecting...
                </span>
            </button>
        </div>

    {{-- ── Step: success ── --}}
    @elseif($step === 'success')
        <div class="text-center space-y-4">
            <div class="w-20 h-20 bg-violet-100 rounded-full flex items-center justify-center mx-auto">
                <i class="fa-solid fa-wifi text-violet-600 text-3xl"></i>
            </div>
            <div>
                <p class="font-bold text-gray-800 text-lg">Access Activated!</p>
                <p class="text-gray-500 text-sm mt-1">
                    Your free internet access is active until
                    <strong>{{ $expiresAt ? \Carbon\Carbon::parse($expiresAt)->format('D, d M Y h:i A') : 'it expires' }}</strong>.
                </p>
            </div>
            <div class="p-3 bg-violet-50 border border-violet-200 rounded-xl text-sm text-violet-700">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Connect to the Wi-Fi network and your device will get online automatically.
                If not, open your browser — you should be connected.
            </div>
            <a href="{{ route('home') }}" class="block text-violet-600 text-sm font-semibold mt-4">
                Go to homepage →
            </a>
        </div>
    @endif

    <p class="text-center text-xs text-gray-400 mt-8">
        <i class="fa-solid fa-shield-halved mr-1"></i>
        Your receipt code is your Wi-Fi pass. One device per code.
    </p>
</div>
