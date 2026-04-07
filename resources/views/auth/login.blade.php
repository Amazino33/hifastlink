{{-- resources/views/auth/login.blade.php --}}

<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="text-4xl font-black text-transparent bg-clip-text bg-primary mb-3">
            Welcome Back
        </h2>
        <p class="text-gray-500">Sign in to continue your journey</p>
    </div>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    {{-- Magic link --}}
    <div class="text-center mb-4">
        <form method="POST" action="{{ route('router.send_link') }}" class="inline-block">
            @csrf
            <input type="email" name="email" placeholder="Email for magic link"
                class="px-3 py-2 rounded-md text-sm" required>
            <button type="submit" class="ml-2 px-3 py-2 bg-primary text-white rounded-md text-sm">
                Send login link
            </button>
        </form>
    </div>

    {{-- No-JS fallback notice for captive portal users --}}
    <noscript>
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Using a voucher code? Enter it in the field below and leave the PIN field blank.
        </div>
    </noscript>

    <form
        method="POST"
        action="{{ route('login') }}"
        class="space-y-6"
        x-data="{
            isVoucher: false,
            voucherPattern: /^VCH-[A-Z0-9]{8}$/i,
            checkTimer: null,

            onLoginInput(val) {
                clearTimeout(this.checkTimer);
                // Debounce slightly so it doesn't fire on every keystroke mid-type
                this.checkTimer = setTimeout(() => {
                    this.isVoucher = this.voucherPattern.test(val.trim());
                }, 200);
            },

            fillPasswordAndSubmit(form) {
                const loginVal = form.querySelector('#login').value.trim();
                if (this.isVoucher || this.voucherPattern.test(loginVal)) {
                    const passField = form.querySelector('#password');
                    passField.removeAttribute('required');
                    passField.value = loginVal;
                }
            }
        }"
        @submit="fillPasswordAndSubmit($el)"
    >
        @csrf

        {{-- Captive portal passthrough params --}}
        @if(request()->hasAny(['link-login', 'link-login-only', 'link-orig', 'link_login', 'link_orig']))
            <input type="hidden" name="link_login" value="{{
                request()->get('link-login') ??
                request()->get('link-login-only') ??
                request()->get('link_login') ??
                request()->get('link-orig') ??
                request()->get('link_orig')
            }}">
            <input type="hidden" name="link_orig" value="{{ request()->get('link-orig') ?? request()->get('link_orig') ?? '' }}">
        @endif
        @foreach (['mac', 'ip', 'router'] as $param)
            @if(request()->has($param))
                <input type="hidden" name="{{ $param }}" value="{{ request()->get($param) }}">
            @endif
        @endforeach
        @if(request()->has('username'))
            <input type="hidden" name="router_username" value="{{ request()->get('username') }}">
        @endif

        {{-- Smart login field --}}
        <div class="group">
            <label for="login" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-envelope mr-2 text-primary"></i>
                Email, Phone, Username or Voucher Code
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-blue-600 transition-colors duration-300">
                    <i class="fa-solid fa-user"></i>
                </div>
                <input
                    id="login"
                    type="text"
                    name="login"
                    value="{{ old('login') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Email, phone, username or VCH-XXXXXXXX"
                    class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('login') border-red-500 ring-4 ring-red-100 @enderror"
                    @input="onLoginInput($event.target.value)"
                >
            </div>

            <div x-show="isVoucher" x-transition class="mt-2 flex items-center gap-2 text-green-600 text-sm font-medium">
                <i class="fa-solid fa-ticket"></i>
                Voucher detected — no PIN needed
            </div>

            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        {{-- Password — hidden for vouchers but always present in DOM so it submits --}}
        <div x-show="!isVoucher" x-transition x-data="{ showPassword: false }" class="group">
            <label for="password" class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-lock mr-2 text-primary"></i>PIN
            </label>
            <div class="relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-primary transition-colors duration-300">
                    <i class="fa-solid fa-key"></i>
                </div>
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    :required="!isVoucher"
                    autocomplete="current-password"
                    placeholder="Enter your PIN"
                    class="w-full pl-12 pr-14 py-4 bg-gray-50 border-2 border-gray-200 rounded-2xl text-gray-800 placeholder-gray-400 focus:bg-white focus:border-primary focus:ring-4 focus:ring-blue-100 transition-all duration-300 @error('password') border-red-500 ring-4 ring-red-100 @enderror"
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

        <div class="flex items-center justify-between">
            <label class="flex items-center cursor-pointer group">
                <input id="remember_me" type="checkbox" name="remember"
                    class="w-5 h-5 text-blue-600 bg-gray-50 border-2 border-gray-300 rounded focus:ring-4 focus:ring-blue-100 transition-all duration-300 cursor-pointer">
                <span class="ml-3 text-sm text-gray-600 group-hover:text-gray-900 transition-colors duration-300">
                    Remember me
                </span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline transition-all duration-300">
                    Forgot PIN?
                </a>
            @endif
        </div>

        <button type="submit"
            class="w-full bg-primary hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 group">
            <span class="flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket group-hover:translate-x-1 transition-transform duration-300"></i>
                <span x-text="isVoucher ? 'Connect' : 'Log in'">Log in</span>
            </span>
        </button>

        <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t-2 border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500 font-medium">New to HiFastLink?</span>
            </div>
        </div>

        @if (Route::has('register'))
            <a href="{{ route('register') }}"
                class="block w-full text-center bg-white border-2 border-gray-300 hover:border-primary text-gray-700 hover:text-primary font-semibold py-4 rounded-2xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg group">
                <i class="fa-solid fa-user-plus mr-2 group-hover:scale-110 inline-block transition-transform duration-300"></i>
                Create Account
            </a>
        @endif
    </form>

    {{-- Captive portal auto-bridge (only fires if already authenticated) --}}
    @if(auth()->check() && request()->hasAny(['link-login', 'link-login-only', 'link-orig']))
        <script>
            (function () {
                const linkLogin = {{ Js::from(request()->get('link-login') ?? request()->get('link-login-only') ?? request()->get('link-orig')) }};
                const mac       = {{ Js::from(request()->get('mac', '')) }};
                const ip        = {{ Js::from(request()->get('ip', '')) }};

                const notice = document.createElement('div');
                notice.className = 'fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm';
                notice.textContent = 'Signing you into the WiFi...';
                document.body.appendChild(notice);

                async function doBridgeLogin() {
                    try {
                        const resp = await fetch('{{ route('router.bridge_login') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ mac, ip, 'link-login': linkLogin }),
                        });

                        const data = await resp.json();

                        if (resp.ok && data.success && data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }

                        if (data.username && data.password && data.login_url) {
                            window.location.href = data.login_url
                                + '?username=' + encodeURIComponent(data.username)
                                + '&password=' + encodeURIComponent(data.password)
                                + '&dst='      + encodeURIComponent(data.dashboard_url || '{{ route('dashboard') }}');
                            return;
                        }

                        notice.textContent = data.message || 'Could not connect automatically.';
                        notice.classList.replace('bg-blue-600', 'bg-red-600');
                    } catch {
                        notice.textContent = 'Network error — please try again.';
                        notice.classList.replace('bg-blue-600', 'bg-red-600');
                    }
                }

                setTimeout(doBridgeLogin, 300);
            })();
        </script>
    @endif
</x-guest-layout>