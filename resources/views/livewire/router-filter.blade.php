<div>
<div class="mb-6">
    <div class="flex overflow-x-auto pb-2 mb-2 no-scrollbar space-x-2">
        <button class="filter-chip inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white shadow-sm ring-2 ring-blue-200 transition hover:bg-blue-700 {{ $selectedRouter === 'all' ? 'bg-blue-600 text-white ring-2 ring-blue-200 shadow' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                wire:click="selectRouter('all')">
            All Locations
        </button>

        @foreach($allRouters as $router)
            @php $id = $router->ip_address ?? ($router->nas_identifier ?? $router->identity); @endphp
            <button class="filter-chip inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition {{ $selectedRouter == $id ? 'bg-blue-600 text-white ring-2 ring-blue-200 shadow' : '' }}"
                    wire:click="selectRouter('{{ $id }}')">
                {{ $router->name ?? $id }}
            </button>
        @endforeach
    </div>
</div>

@push('styles')
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush

</div>