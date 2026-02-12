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

<script>
    console.log("Router filter widget script loaded");
    // Delegated click handler for filter chips (robust when widgets load dynamically)
    document.addEventListener('click', function (e) {
        const el = e.target.closest && e.target.closest('.filter-chip');
        if (!el) return;
        console.log("Click detected on filter chip", el);

        // Visual toggle
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('btn-primary','shadow-sm','text-white'));
        el.classList.add('btn-primary','shadow-sm','text-white');

        const routerId = el.dataset.routerId || 'all';

        // Update URL without reloading
        const url = new URL(window.location.href);
        url.searchParams.set('router_id', routerId);
        window.history.replaceState({}, '', url.toString());

        // Fetch stats and update Filament widgets (with logging & fallback)
        const apiUrl = `{{ route('api.admin.stats') }}?router_id=${encodeURIComponent(routerId)}`;
        console.debug('[router-filter] fetching stats for', routerId, apiUrl);

        fetch(apiUrl)
            .then(r => {
                console.debug('[router-filter] response status', r.status);
                if (!r.ok) throw new Error('Non-OK response: ' + r.status);
                return r.json();
            })
            .then(data => {
                console.debug('[router-filter] stats payload', data);

                // Prefer widget updater when available
                if (typeof updateFilamentStats === 'function') {
                    updateFilamentStats(data);
                    return;
                }

                // Fallback: update DOM elements directly
                if (document.getElementById('filament-stat-online-users')) {
                    document.getElementById('filament-stat-online-users').innerText = data.online_users ?? '0';
                }
                if (document.getElementById('filament-stat-revenue')) {
                    document.getElementById('filament-stat-revenue').innerText = '₦' + (data.today_revenue ? new Intl.NumberFormat().format(data.today_revenue) : '0');
                }
                if (document.getElementById('filament-stat-subscribers')) {
                    document.getElementById('filament-stat-subscribers').innerText = data.active_subscribers ?? '0';
                }
                if (document.getElementById('filament-stat-data-usage')) {
                    document.getElementById('filament-stat-data-usage').innerText = data.data_consumed ?? '0 B';
                }
            })
            .catch(err => {
                console.error('[router-filter] Failed to fetch/update stats:', err);
                // show a subtle visual feedback on failure
                el.classList.add('ring-2','ring-red-400');
                setTimeout(() => el.classList.remove('ring-2','ring-red-400'), 1500);
            });
    });
</script>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>