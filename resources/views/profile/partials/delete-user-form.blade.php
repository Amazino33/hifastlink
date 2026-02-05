<section class="space-y-6">
    <header>
        <h2 class="text-xl font-black text-red-600 dark:text-red-400">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i>
            {{ __('Danger Zone') }}
        </h2>

        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 font-semibold">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <div class="flex items-center justify-between p-4 bg-red-100 dark:bg-red-900/30 border-2 border-red-300 dark:border-red-700 rounded-xl">
        <div class="flex-1">
            <h3 class="font-bold text-red-900 dark:text-red-200 flex items-center gap-2">
                <i class="fa-solid fa-trash-can"></i>
                Delete Account Permanently
            </h3>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                This action cannot be undone. All your data will be lost forever.
            </p>
        </div>
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="ml-4 flex items-center gap-2"
        >
            <i class="fa-solid fa-trash"></i>
            {{ __('Delete Account') }}
        </x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-exclamation-triangle text-3xl text-red-600 dark:text-red-400"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100">
                    {{ __('Are you absolutely sure?') }}
                </h2>
            </div>

            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-sm text-red-800 dark:text-red-200 font-semibold">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    {{ __('This action cannot be undone. This will permanently delete your account and remove all your data from our servers.') }}
                </p>
            </div>

            <div class="mb-6">
                <x-input-label for="password" value="{{ __('Enter your password to confirm')} }" />
                <div class="relative mt-2">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-key text-gray-400"></i>
                    </div>
                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="block w-full pl-10"
                        placeholder="{{ __('Your current password') }}"
                    />
                </div>
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="flex items-center gap-2">
                    <i class="fa-solid fa-xmark"></i>
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="flex items-center gap-2">
                    <i class="fa-solid fa-trash-can"></i>
                    {{ __('Yes, Delete My Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
