{{-- resources/views/auth/login.blade.php --}}

<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="text-4xl font-black text-transparent bg-clip-text bg-primary mb-3">
            Welcome Back
        </h2>
        <p class="text-gray-500">Sign in to continue your journey</p>
    </div>

    <x-auth-session-status class="mb-6" :status="session('status')" />

    {{-- Login method tabs --}}
    <div x-data="{ tab: 'pin' }" class="mb-6">
        <div class="flex rounded-2xl bg-gray-100 p-1 mb-6">
            <button
                @click="tab = 'pin'"
                :class="tab === 'pin' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200"
            >
                <i class="fa-solid fa-key mr-1.5"></i> PIN / Password
            </button>
            <button
                @click="tab = 'otp'"
                :class="tab === 'otp' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200"
            >
                <i class="fa-brands fa-whatsapp mr-1.5"></i> WhatsApp OTP
            </button>
        </div>

        {{-- PIN / Password form --}}
        <div x-show="tab === 'pin'" x-cloak>

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
            voucherPattern: /^VCH-[A-Z0-9]+$/i,
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
                    placeholder="Email, phone, username or voucher code"
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
                <span class="px-4 bg-white text-gray-500 font-medium">Or continue with</span>
            </div>
        </div>

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
        </div>{{-- end tab: pin --}}

        {{-- OTP tab --}}
        <div x-show="tab === 'otp'" x-cloak>
            @livewire('phone-otp-login')

            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t-2 border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500 font-medium">Or continue with</span>
                </div>
            </div>

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

            @if (Route::has('register'))
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t-2 border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500 font-medium">New to HiFastLink?</span>
                    </div>
                </div>
                <a href="{{ route('register') }}"
                    class="block w-full text-center bg-white border-2 border-gray-300 hover:border-primary text-gray-700 hover:text-primary font-semibold py-4 rounded-2xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg group">
                    <i class="fa-solid fa-user-plus mr-2 group-hover:scale-110 inline-block transition-transform duration-300"></i>
                    Create Account
                </a>
            @endif
        </div>{{-- end tab: otp --}}

    </div>{{-- end x-data tabs --}}

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