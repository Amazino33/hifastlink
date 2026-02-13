<div>
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Request Custom Data Plans</h2>

        <form wire:submit.prevent="submitRequest" class="space-y-6">
            <!-- Router Selection -->
            <div>
                <label for="router_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Router
                </label>
                <select
                    wire:model="router_id"
                    id="router_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('router_id') border-red-500 @enderror"
                >
                    <option value="">Choose a router...</option>
                    @foreach($routers as $router)
                        <option value="{{ $router->id }}">{{ $router->name }} ({{ $router->location }})</option>
                    @endforeach
                </select>
                @error('router_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Universal Plans Toggle -->
            <div class="flex items-center">
                <input
                    wire:model="show_universal_plans"
                    type="checkbox"
                    id="show_universal_plans"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label for="show_universal_plans" class="ml-2 block text-sm text-gray-900">
                    Show universal plans alongside custom plans
                </label>
            </div>

            <!-- Plans Section -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Custom Plans</h3>
                    <button
                        type="button"
                        wire:click="addPlan"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Plan
                    </button>
                </div>

                <div class="space-y-4">
                    @foreach($plans as $index => $plan)
                        <div class="border border-gray-200 rounded-lg p-4 @if($loop->first) bg-gray-50 @endif">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-sm font-medium text-gray-900">Plan {{ $index + 1 }}</h4>
                                @if(count($plans) > 1)
                                    <button
                                        type="button"
                                        wire:click="removePlan({{ $index }})"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        Remove
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Plan Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Plan Name *
                                    </label>
                                    <input
                                        wire:model="plans.{{ $index }}.name"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('plans.' . $index . '.name') border-red-500 @enderror"
                                        placeholder="e.g., Premium 10GB"
                                    >
                                    @error('plans.' . $index . '.name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Data Limit -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Data Limit (MB) *
                                    </label>
                                    <input
                                        wire:model="plans.{{ $index }}.data_limit"
                                        type="number"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('plans.' . $index . '.data_limit') border-red-500 @enderror"
                                        placeholder="e.g., 10240"
                                    >
                                    @error('plans.' . $index . '.data_limit')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Duration -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Duration (Days) *
                                    </label>
                                    <input
                                        wire:model="plans.{{ $index }}.duration_days"
                                        type="number"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('plans.' . $index . '.duration_days') border-red-500 @enderror"
                                        placeholder="30"
                                    >
                                    @error('plans.' . $index . '.duration_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Price (₦) *
                                    </label>
                                    <input
                                        wire:model="plans.{{ $index }}.price"
                                        type="number"
                                        step="0.01"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('plans.' . $index . '.price') border-red-500 @enderror"
                                        placeholder="e.g., 5000.00"
                                    >
                                    @error('plans.' . $index . '.price')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Max Devices (Optional) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Max Simultaneous Devices (Optional)
                                    </label>
                                    <input
                                        wire:model="plans.{{ $index }}.max_devices"
                                        type="number"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('plans.' . $index . '.max_devices') border-red-500 @enderror"
                                        placeholder="e.g., 3"
                                    >
                                    @error('plans.' . $index . '.max_devices')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Speed Limit (Optional) -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Speed Limit (Optional)
                                    </label>
                                    <input
                                        wire:model="plans.{{ $index }}.speed_limit"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., 10Mbps, Unlimited"
                                    >
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('plans')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Submit Request</span>
                    <span wire:loading>Submitting...</span>
                </button>
            </div>
        </form>

        <!-- Success Message -->
        @if ($showSuccessMessage && $successMessage)
            <div
                class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-sm"
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 5000)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
            >
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $successMessage }}
                </div>
            </div>
        @endif

        <!-- Error Message -->
        @if ($showErrorMessage && $errorMessage)
            <div
                class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow-sm"
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 5000)"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
            >
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $errorMessage }}
                </div>
            </div>
        @endif
    </div>
</div>