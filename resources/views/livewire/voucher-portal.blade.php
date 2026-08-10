<div>
    @if(! $integration)
        <div class="text-center py-8">
            <p class="text-red-500 font-semibold">Integration not found or inactive.</p>
        </div>
    @else

    {{-- Header --}}
    <div class="text-center mb-8">
        <h2 class="text-3xl font-black mb-2" style="color: {{ $integration->primary_color }}">
            @if($step === 'code') Free Internet
            @else You're Connected!
            @endif
        </h2>
        <p class="text-gray-500 text-sm">
            @if($step === 'code') {{ $integration->portal_subtitle ?? 'Enter the code from your receipt' }}
            @else Your free internet access is now active
            @endif
        </p>
    </div>

    {{-- Error --}}
    @if($error)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> {{ $error }}
        </div>
    @endif

    @if($step === 'code')
        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">
                    <i class="{{ $integration->icon }} mr-2" style="color: {{ $integration->primary_color }}"></i>
                    {{ $integration->code_label }}
                </label>
                <input
                    wire:model="code"
                    wire:keydown.enter="validateCode"
                    type="text"
                    placeholder="{{ $integration->code_placeholder ?? 'Enter code' }}"
                    autocomplete="off"
                    maxlength="{{ $integration->code_maxlength }}"
                    class="w-full px-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 text-sm focus:outline-none focus:bg-white transition-all duration-300 uppercase tracking-widest"
                    style="--tw-border-opacity: 1;"
                    onfocus="this.style.borderColor='{{ $integration->primary_color }}'"
                    onblur="this.style.borderColor=''"
                >
            </div>

            <button
                wire:click="validateCode"
                wire:loading.attr="disabled"
                class="w-full text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 text-sm flex items-center justify-center gap-2 opacity-90 hover:opacity-100"
                style="background-color: {{ $integration->primary_color }}">
                <span wire:loading.remove wire:target="validateCode">
                    <i class="fa-solid fa-wifi mr-1"></i> Connect to Wi-Fi
                </span>
                <span wire:loading wire:target="validateCode">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Connecting...
                </span>
            </button>
        </div>

    @elseif($step === 'success')
        <div class="text-center space-y-4">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto" style="background-color: {{ $integration->primary_color }}1a">
                <i class="fa-solid fa-wifi text-3xl" style="color: {{ $integration->primary_color }}"></i>
            </div>
            <div>
                <p class="font-bold text-gray-800 text-lg">Access Activated!</p>
                <p class="text-gray-500 text-sm mt-1">
                    Your free internet access is active until
                    <strong>{{ $expiresAt ? \Carbon\Carbon::parse($expiresAt)->format('D, d M Y h:i A') : 'it expires' }}</strong>.
                </p>
            </div>
            <div class="p-3 rounded-xl text-sm" style="background-color: {{ $integration->primary_color }}15; border: 1px solid {{ $integration->primary_color }}40; color: {{ $integration->primary_color }}">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Connect to the Wi-Fi network and your device will get online automatically.
            </div>
            <a href="{{ route('home') }}" class="block text-sm font-semibold mt-4" style="color: {{ $integration->primary_color }}">
                Go to homepage →
            </a>
        </div>
    @endif

    <p class="text-center text-xs text-gray-400 mt-8">
        <i class="fa-solid fa-shield-halved mr-1"></i>
        Your receipt code is your Wi-Fi pass. One device per code.
    </p>

    @endif
</div>
