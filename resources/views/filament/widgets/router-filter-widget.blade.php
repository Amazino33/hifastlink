<div class="mb-4">
    <div class="d-flex overflow-auto pb-2 mb-2 no-scrollbar">
        <button class="btn btn-sm me-2 filter-chip {{ $currentRouter === 'all' ? 'btn-primary shadow-sm text-white' : 'btn-light text-dark border-0' }}"
                data-router-id="all">
            All Locations
        </button>

        @foreach($allRouters as $router)
            @php $id = $router->ip_address ?? ($router->nas_identifier ?? $router->identity); @endphp
            <button class="btn btn-sm me-2 filter-chip {{ $currentRouter == $id ? 'btn-primary shadow-sm text-white' : 'btn-light text-dark border-0' }}"
                    data-router-id="{{ $id }}">
                {{ $router->name ?? $id }}
            </button>
        @endforeach

<script>
    // Delegated click handler for filter chips (robust when widgets load dynamically)
    document.addEventListener('click', function (e) {
        const el = e.target.closest && e.target.closest('.filter-chip');
        if (!el) return;

        // Visual toggle
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('btn-primary','shadow-sm','text-white'));
        el.classList.add('btn-primary','shadow-sm','text-white');

        const routerId = el.dataset.routerId || 'all';

        // Update URL without reloading
        const url = new URL(window.location.href);
        url.searchParams.set('router_id', routerId);
        window.history.replaceState({}, '', url.toString());

        // Fetch stats and update Filament widgets
        fetch(`{{ route('api.admin.stats') }}?router_id=${encodeURIComponent(routerId)}`)
            .then(r => r.json())
            .then(data => {
                if (typeof updateFilamentStats === 'function') {
                    updateFilamentStats(data);
                }
            })
            .catch(e => console.error('Failed to fetch stats', e));
    });
</script>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>