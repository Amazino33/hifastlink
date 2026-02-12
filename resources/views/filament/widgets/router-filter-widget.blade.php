<div class="mb-4">
    <div class="d-flex overflow-auto pb-2 mb-2 no-scrollbar">
        <button class="btn btn-sm me-2 filter-chip {{ $currentRouter === 'all' ? 'btn-primary shadow-sm text-white' : 'btn-light text-dark border-0' }}"
                onclick="(function(){ const p = new URLSearchParams(window.location.search); p.set('router_id','all'); window.location.search = p.toString(); })()">
            All Locations
        </button>

        @foreach($allRouters as $router)
            @php $id = $router->ip_address ?? ($router->nas_identifier ?? $router->identity); @endphp
            <button class="btn btn-sm me-2 filter-chip {{ $currentRouter == $id ? 'btn-primary shadow-sm text-white' : 'btn-light text-dark border-0' }}"
                    onclick="(function(){ const p = new URLSearchParams(window.location.search); p.set('router_id','{{ $id }}'); window.location.search = p.toString(); })()">
                {{ $router->name ?? $id }}
            </button>
        @endforeach
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>