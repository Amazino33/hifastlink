<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Profile Overview Card --}}
            <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-2xl shadow-xl text-white">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        <div class="w-32 h-32 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-6xl font-black border-4 border-white/30">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </div>

                    {{-- User Info --}}
                    <div class="flex-1 text-center md:text-left">
                        <h3 class="text-3xl font-black mb-2">{{ $user->name }}</h3>
                        <p class="text-blue-100 mb-1">@<span class="font-semibold">{{ $user->username }}</span></p>
                        <p class="text-blue-100 mb-4">{{ $user->email }}</p>
                        
                        <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                            @if($user->plan)
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-white/20 backdrop-blur-sm">
                                    <i class="fa-solid fa-crown mr-2"></i>
                                    {{ $user->plan->name }}
                                </span>
                            @endif
                            
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-white/20 backdrop-blur-sm">
                                <i class="fa-solid fa-calendar-check mr-2"></i>
                                Joined {{ $user->created_at->format('M Y') }}
                            </span>

                            @if($user->email_verified_at)
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-500/30 backdrop-blur-sm">
                                    <i class="fa-solid fa-circle-check mr-2"></i>
                                    Verified
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Personal Information --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="max-w-4xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            {{-- Account Statistics --}}
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-gray-600 dark:text-gray-400 text-sm font-semibold">Data Used</div>
                        <i class="fa-solid fa-database text-primary text-xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white">
                        {{ \Illuminate\Support\Number::fileSize($user->data_used ?? 0) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-gray-600 dark:text-gray-400 text-sm font-semibold">Data Limit</div>
                        <i class="fa-solid fa-chart-line text-green-600 text-xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white">
                        @if($user->data_limit)
                            {{ \Illuminate\Support\Number::fileSize($user->data_limit) }}
                        @else
                            <span class="text-blue-600">Unlimited</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-gray-600 dark:text-gray-400 text-sm font-semibold">Plan Status</div>
                        <i class="fa-solid fa-signal text-blue-600 text-xl"></i>
                    </div>
                    <div class="text-3xl font-black text-gray-900 dark:text-white capitalize">
                        {{ $user->connection_status ?? 'Inactive' }}
                    </div>
                </div>
            </div>

            {{-- Security Settings --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="max-w-4xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Danger Zone --}}
            <div class="p-4 sm:p-8 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 shadow-xl rounded-2xl">
                <div class="max-w-4xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
