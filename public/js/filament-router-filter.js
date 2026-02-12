// Delegated click handler for router filter chips in Filament
document.addEventListener('click', function (e) {
    const el = e.target.closest && e.target.closest('.filter-chip');
    if (!el) return;

    console.log("Click detected on filter chip", el);

    // Visual toggle
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('bg-blue-600','text-white','ring-2','ring-blue-200','shadow'));
    el.classList.add('bg-blue-600','text-white','ring-2','ring-blue-200','shadow');

    const routerId = el.dataset.routerId || 'all';

    // Update URL without reloading
    const url = new URL(window.location.href);
    url.searchParams.set('router_id', routerId);
    window.history.replaceState({}, '', url.toString());

    // Fetch stats and update Filament widgets (with logging & fallback)
    const apiUrl = `/api/admin/stats?router_id=${encodeURIComponent(routerId)}`;
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

// Provide a global function so RouterFilterWidget can call it to update stats
function updateFilamentStats(data) {
    if (document.getElementById('filament-stat-online-users'))
        document.getElementById('filament-stat-online-users').innerText = data.online_users;
    if (document.getElementById('filament-stat-revenue'))
        document.getElementById('filament-stat-revenue').innerText = '₦' + new Intl.NumberFormat().format(data.today_revenue);
    if (document.getElementById('filament-stat-subscribers'))
        document.getElementById('filament-stat-subscribers').innerText = data.active_subscribers;
    if (document.getElementById('filament-stat-data-usage'))
        document.getElementById('filament-stat-data-usage').innerText = data.data_consumed;
}