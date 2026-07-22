<div>
    {{-- Header --}}
    <div class="text-center mb-6">
        <h2 class="text-3xl font-black text-transparent bg-clip-text bg-primary mb-2">
            Get Connected
        </h2>
        <p class="text-gray-500 text-sm">Type below to connect instantly.</p>
    </div>

    {{-- Who are you? --}}
    <div class="mb-6 grid grid-cols-2 gap-2 text-xs">
        <div class="bg-gray-50 rounded-xl p-3">
            <p class="font-semibold text-gray-700 mb-1">
                <i class="fa-solid fa-user text-primary mr-1"></i> Subscriber
            </p>
            <p class="text-gray-500 leading-snug">Enter your phone number, email, or username</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3">
            <p class="font-semibold text-gray-700 mb-1">
                <i class="fa-solid fa-ticket text-primary mr-1"></i> Voucher
            </p>
            <p class="text-gray-500 leading-snug">Enter the code on your voucher card (VCH-…)</p>
        </div>
    </div>

    {{-- No plan state --}}
    @if($noplan)
        <div class="text-center space-y-4">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto">
                <i class="fa-solid fa-wifi-exclamation text-2xl text-amber-500"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800">No active plan</p>
                <p class="text-sm text-gray-500 mt-1">Your plan has expired or run out of data.</p>
            </div>
            <a href="https://hifastlink.com/dashboard"
               target="_blank"
               class="block w-full py-3 px-4 bg-primary text-white rounded-xl font-semibold text-sm text-center">
                Subscribe Now
            </a>
            <button wire:click="$set('noplan', false)"
                    class="text-sm text-gray-400 hover:text-gray-600">
                Try a different account
            </button>
        </div>

    {{-- Main form --}}
    @else
        @if($error)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $error }}
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <input
                    wire:model="identifier"
                    wire:keydown.enter="connect"
                    type="text"
                    placeholder="Phone, email, username, or voucher"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                />
            </div>

            <button
                wire:click="connect"
                wire:loading.attr="disabled"
                wire:target="connect"
                class="w-full py-3 px-4 bg-primary text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-60">
                <span wire:loading.remove wire:target="connect">Connect</span>
                <span wire:loading wire:target="connect" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Connecting...
                </span>
            </button>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            Don't have an account?
            <a href="https://hifastlink.com/dashboard" target="_blank" class="text-primary font-medium">Subscribe here</a>
        </p>
    @endif
</div>
