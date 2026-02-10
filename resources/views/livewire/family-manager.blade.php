<div>
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 shadow-xl">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-3xl font-black text-gray-900 dark:text-white">My Family</h1>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $familyMembers->count() }} / {{ Auth::user()->family_limit }} members
                </div>
            </div>

            <!-- Add Member Form -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Add Family Member</h2>

                @if(Auth::user()->children()->count() >= Auth::user()->family_limit)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
                        <div class="flex items-center">
                            <i class="fa-solid fa-exclamation-triangle text-red-500 mr-3"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Family Limit Reached</h3>
                                <p class="text-sm text-red-700 dark:text-red-300">You have reached your maximum family member limit of {{ Auth::user()->family_limit }}.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <form wire:submit.prevent="addMember" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                            <input type="text" wire:model="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Full Name">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                            <input type="text" wire:model="username" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Username">
                            @error('username') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                            <input type="password" wire:model="password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Password">
                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-3">
                            <button type="submit" onclick="return confirm('Are you sure you want to add {{ $name ?: 'this member' }} to your family? This will share your plan data and time with them.')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-300">
                                Add Member
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Link Existing User Form -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Link Existing Account</h2>

                @if(Auth::user()->children()->count() >= Auth::user()->family_limit)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
                        <div class="flex items-center">
                            <i class="fa-solid fa-exclamation-triangle text-red-500 mr-3"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Family Limit Reached</h3>
                                <p class="text-sm text-red-700 dark:text-red-300">You have reached your maximum family member limit of {{ Auth::user()->family_limit }}.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <form wire:submit.prevent="addExistingMember" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Existing Username</label>
                            <input type="text" wire:model="existingUsername" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="Enter existing username">
                            @error('existingUsername') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-end">
                            <button type="submit" onclick="return confirm('Are you sure you want to link {{ $existingUsername ?: 'this user' }} to your family? This will share your plan data and time with them.')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors duration-300">
                                Link User
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Family Members List -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Family Members</h2>

                @if($familyMembers->count() > 0)
                    <div class="space-y-4">
                        @foreach($familyMembers as $member)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                        <i class="fa-solid fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $member->name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $member->username }}</p>
                                    </div>
                                </div>

                                <button wire:click="removeMember({{ $member->id }})" wire:confirm="Are you sure you want to remove {{ $member->name }}?" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                    Remove
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fa-solid fa-users text-gray-300 dark:text-gray-600 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Family Members</h3>
                        <p class="text-gray-500 dark:text-gray-400">Add your first family member above.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>