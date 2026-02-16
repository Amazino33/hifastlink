<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Router Analytics — {{ $router->name ?? $router->nas_identifier }}</h1>
            <p class="text-sm text-gray-500">Showing analytics for the router assigned to your account.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Online Users</div>
                <div id="stat-online-users" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">0</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Today's Revenue</div>
                <div id="stat-revenue" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">₦0</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active Subscribers</div>
                <div id="stat-subscribers" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">0</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Data Usage</div>
                <div id="stat-data-usage" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">0 B</div>
            </div>
        </div>

        <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Recent Sessions (Router)</h2>
            <div id="recent-sessions-placeholder" class="text-sm text-gray-500">Loading sessions...</div>
        </div>
    </div>

    <script>
        (function(){
            const routerId = @json($router->id);
            fetch(`{{ route('api.admin.stats') }}?router_id=${routerId}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('stat-online-users').innerText = data.online_users ?? 0;
                    document.getElementById('stat-revenue').innerText = '₦' + (data.today_revenue ? new Intl.NumberFormat().format(data.today_revenue) : '0');
                    document.getElementById('stat-subscribers').innerText = data.active_subscribers ?? 0;
                    document.getElementById('stat-data-usage').innerText = data.data_consumed ?? '0 B';
                }).catch(err => {
                    console.error(err);
                });

            // Fetch recent sessions (best-effort) via simple AJAX to the stats endpoint is not supported,
            // so show a small helper linked to the admin dashboard for deeper inspection.
            document.getElementById('recent-sessions-placeholder').innerHTML = `<a href="${window.location.origin + '/admin/dashboard?router_id=' + routerId}" class="text-blue-600 hover:underline">Open admin dashboard (filtered)</a>`;
        })();
    </script>
</x-app-layout>
