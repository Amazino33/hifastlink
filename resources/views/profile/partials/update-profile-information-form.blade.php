<section>
    <header>
        <h2 class="text-xl font-black text-gray-900 dark:text-gray-100">
            <i class="fa-solid fa-user-circle mr-2 text-primary"></i>
            {{ __('Personal Information') }}
        </h2>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's personal information and contact details.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Full Name --}}
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-user text-gray-400"></i>
                </div>
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full pl-10" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- Username --}}
        <div>
            <x-input-label for="username" :value="__('Username')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-at text-gray-400"></i>
                </div>
                <x-text-input id="username" name="username" type="text" class="mt-1 block w-full pl-10" :value="old('username', $user->username)" required autocomplete="username" />
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-circle-info"></i> This is your login username. Must be alphanumeric.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-envelope text-gray-400"></i>
                </div>
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full pl-10" :value="old('email', $user->email)" required autocomplete="username" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-center gap-2 text-sm text-yellow-800 dark:text-yellow-200 mb-2">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        {{ __('Your email address is unverified.') }}
                    </div>

                    <button form="send-verification" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                            <i class="fa-solid fa-circle-check"></i>
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Phone Number --}}
        <div>
            <x-input-label for="phone" :value="__('Phone Number')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-phone text-gray-400"></i>
                </div>
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full pl-10" :value="old('phone', $user->phone)" autocomplete="tel" placeholder="+123 456 7890" />
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-circle-info"></i> Optional. Used for account recovery and notifications.
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        {{-- Save Button --}}
        <div class="flex items-center gap-4 pt-4">
            <x-primary-button class="flex items-center gap-2">
                <i class="fa-solid fa-save"></i>
                {{ __('Save Changes') }}
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 dark:text-green-400 flex items-center gap-2"
                >
                    <i class="fa-solid fa-circle-check"></i>
                    {{ __('Saved successfully!') }}
                </p>
            @endif
        </div>
    </form>
</section>
