<div>
    @php
        $accentColor  = $brandColor ?: '#007AFE';
        $isBranded    = !empty($brandName);
        $btnStyle     = "background-color: {$accentColor};";
        $borderStyle  = "border-color: {$accentColor};";
        $iconStyle    = "color: {$accentColor};";

        $heading    = $brandHeading    ?: 'Get Connected';
        $subheading = $brandSubheading ?: 'Type below to connect instantly.';
        $btnText    = $brandButtonText ?: 'Connect';
        $inputPlaceholder = $brandInputPlaceholder ?: 'Phone, email, username, or voucher';
        $helpText   = $brandHelpText;
    @endphp

    {{-- Header --}}
    <div class="text-center mb-6">
        <h2 class="text-3xl font-black text-gray-800 mb-2">{{ $heading }}</h2>
        <p class="text-gray-500 text-sm">{{ $subheading }}</p>
    </div>

    {{-- Access instructions --}}
    @php
        $instructions = (! empty($brandInstructions)) ? $brandInstructions : [
            ['icon' => 'fa-user',   'title' => 'Subscriber', 'description' => 'Enter your phone number, email, or username'],
            ['icon' => 'fa-ticket', 'title' => 'Voucher',    'description' => 'Enter the code on your voucher card (VCH-…)'],
        ];
        $cols = count($instructions) === 1 ? 1 : 2;
    @endphp
    <div class="mb-6 grid gap-2 text-xs" style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr));">
        @foreach ($instructions as $instr)
            <div class="bg-gray-50 rounded-xl p-3">
                <p class="font-semibold text-gray-700 mb-1">
                    <i class="fa-solid {{ $instr['icon'] ?? 'fa-circle-info' }} mr-1" style="{{ $iconStyle }}"></i>
                    {{ $instr['title'] ?? '' }}
                </p>
                <p class="text-gray-500 leading-snug">{{ $instr['description'] ?? '' }}</p>
            </div>
        @endforeach
    </div>

    {{-- No plan state --}}
    @if($noplan)
        <div class="text-center space-y-4">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto">
                <i class="fa-solid fa-wifi-exclamation text-2xl text-amber-500"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800">No active plan</p>
                <p class="text-sm text-gray-500 mt-1">
                    @if($isBranded)
                        Your plan has expired. Please visit the reception desk for assistance.
                    @else
                        Your plan has expired or run out of data.
                    @endif
                </p>
            </div>
            @if($isBranded)
                <div class="py-3 px-4 rounded-xl text-sm text-center font-medium text-white" style="{{ $btnStyle }}">
                    Please contact the reception desk
                </div>
            @else
                <a href="https://app.hifastlink.com"
                   target="_blank"
                   class="block w-full py-3 px-4 text-white rounded-xl font-semibold text-sm text-center"
                   style="{{ $btnStyle }}">
                    Subscribe Now
                </a>
            @endif
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
                    placeholder="{{ $inputPlaceholder }}"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                    style="--tw-ring-color: {{ $accentColor }}40;"
                    onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px {{ $accentColor }}30';"
                    onblur="this.style.borderColor=''; this.style.boxShadow='';"
                />
            </div>

            <button
                wire:click="connect"
                wire:loading.attr="disabled"
                wire:target="connect"
                class="w-full py-3 px-4 text-white rounded-xl font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-60 transition"
                style="{{ $btnStyle }}">
                <span wire:loading.remove wire:target="connect">{{ $btnText }}</span>
                <span wire:loading wire:target="connect" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Connecting...
                </span>
            </button>
        </div>

        @if(! $isBranded)
            <div class="relative my-4">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center text-xs text-gray-400">
                    <span class="bg-white px-2">or get connected with</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('auth.google', array_filter(['link-login' => $linkLogin, 'mac' => $mac, 'ip' => $ip, 'router' => $router])) }}"
                   class="flex items-center justify-center gap-1.5 py-2.5 px-3 border border-gray-200 rounded-xl hover:bg-gray-50 text-xs font-semibold text-gray-700 transition shadow-sm">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </a>

                <a href="{{ route('login', array_filter(['tab' => 'whatsapp', 'link-login' => $linkLogin, 'mac' => $mac, 'ip' => $ip, 'router' => $router])) }}"
                   class="flex items-center justify-center gap-1.5 py-2.5 px-3 border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-50 rounded-xl text-xs font-semibold text-emerald-700 transition shadow-sm">
                    <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                    WhatsApp
                </a>
            </div>
        @endif

        <p class="text-center text-xs text-gray-400 mt-6">
            @if($helpText)
                {{ $helpText }}
                @if($brandHelpLinkText && $brandHelpLinkUrl)
                    <a href="{{ $brandHelpLinkUrl }}"
                       class="font-medium"
                       style="{{ $iconStyle }}"
                       @if(str_starts_with($brandHelpLinkUrl, 'http')) target="_blank" rel="noopener" @endif
                    >{{ $brandHelpLinkText }}</a>
                @elseif($brandHelpLinkText)
                    <span class="font-medium text-gray-600">{{ $brandHelpLinkText }}</span>
                @endif
            @elseif($isBranded)
                Need WiFi access? <span class="font-medium text-gray-600">Visit the reception desk</span>
            @else
                Need to manage plans?
                <a href="{{ route('login') }}"
                   class="font-medium" style="{{ $iconStyle }}">Sign in to Customer App</a>
            @endif
        </p>
    @endif
</div>
