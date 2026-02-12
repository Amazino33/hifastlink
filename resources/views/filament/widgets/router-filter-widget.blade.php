<div class="mb-6">
    <!-- Search Bar -->
    <div class="mb-4">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Search routers by name, location, or identifier..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
    </div>

    <!-- Router Chips -->
    <div class="flex overflow-x-auto pb-2 space-x-2" style="-ms-overflow-style: none; scrollbar-width: none;">
        <div style="display: none;" class="webkit-scrollbar-hide">
            <style>
                .webkit-scrollbar-hide::-webkit-scrollbar { display: none; }
            </style>
        </div>
        <button class="filter-chip inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 whitespace-nowrap {{ ($currentRouter ?? 'all') === 'all' ? 'bg-blue-600 text-white ring-2 ring-blue-300 shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-sm' }}"
                wire:click="selectRouter('all')">
            All Locations
        </button>

        @foreach($allRouters as $router)
            @php $id = $router->nas_identifier ?? $router->ip_address ?? $router->id; @endphp
            <button class="filter-chip inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 whitespace-nowrap {{ ($currentRouter ?? '') == $id ? 'bg-blue-600 text-white ring-2 ring-blue-300 shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-sm' }}"
                    wire:click="selectRouter('{{ $id }}')">
                {{ $router->name ?? $id }}
                @if($router->is_online)
                    <span class="ml-1 w-2 h-2 bg-green-500 rounded-full"></span>
                @else
                    <span class="ml-1 w-2 h-2 bg-red-500 rounded-full"></span>
                @endif
            </button>
        @endforeach
    </div>

    <!-- Current Router Info -->
    @if($currentRouterModel)
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-blue-900">
                    Current Router: {{ $currentRouterModel->name ?? $currentRouterModel->nas_identifier }}
                </h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $currentRouterModel->is_online ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $currentRouterModel->is_online ? 'Online' : 'Offline' }}
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                <div>
                    <span class="font-medium">Location:</span> {{ $currentRouterModel->location ?? 'Not specified' }}
                </div>
                <div>
                    <span class="font-medium">Last Seen:</span>
                    {{ $currentRouterModel->last_seen_at ? $currentRouterModel->last_seen_at->diffForHumans() : 'Never' }}
                </div>
            </div>
        </div>
    @elseif($currentRouter === 'all')
        <div class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-900">All Locations</h3>
            <p class="text-sm text-gray-600">Showing data from all routers</p>
        </div>
    @endif
</div>



