<div class="mb-4">
    <div class="d-flex overflow-auto pb-2 mb-2 no-scrollbar">
        <button class="filter-chip inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 transition mr-2 {{ $currentRouter === 'all' ? 'bg-blue-600 text-white ring-2 ring-blue-200 shadow' : '' }}"
                data-router-id="all">
            All Locations
        </button>

        @foreach($allRouters as $router)
            @php $id = $router->ip_address ?? ($router->nas_identifier ?? $router->identity); @endphp
            <button class="filter-chip inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 transition mr-2 {{ $currentRouter == $id ? 'bg-blue-600 text-white ring-2 ring-blue-200 shadow' : '' }}"
                    data-router-id="{{ $id }}">
                {{ $router->name ?? $id }}
            </button>
        @endforeach



