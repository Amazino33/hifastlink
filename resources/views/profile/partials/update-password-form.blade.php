<section>
    <header>
        <h2 class="text-xl font-black text-gray-900 dark:text-gray-100">
            <i class="fa-solid fa-lock mr-2 text-primary"></i>
            {{ __('Update Password') }}
        </h2>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        {{-- Current Password --}}
        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-key text-gray-400"></i>
                </div>
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full pl-10" autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        {{-- New Password --}}
        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-lock text-gray-400"></i>
                </div>
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full pl-10" autocomplete="new-password" />
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-shield-halved"></i> Use at least 8 characters with a mix of letters, numbers, and symbols.
            </p>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        {{-- Confirm Password --}}
        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-lock text-gray-400"></i>
                </div>
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full pl-10" autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- Save Button --}}
        <div class="flex items-center gap-4 pt-4">
            <x-primary-button class="flex items-center gap-2">
                <i class="fa-solid fa-save"></i>
                {{ __('Update Password') }}
            </x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 dark:text-green-400 flex items-center gap-2"
                >
                    <i class="fa-solid fa-circle-check"></i>
                    {{ __('Password updated successfully!') }}
                </p>
            @endif
        </div>
    </form>
</section>
