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

            @if(isset($recentSessions) && $recentSessions->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">User</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Username</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">MAC</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">IP</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Started</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Stopped</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($recentSessions as $s)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $s->username ? (\App\Models\User::where('username', $s->username)->value('name') ?? '-') : '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $s->username ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $s->callingstationid ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $s->framedipaddress ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ optional($s->acctstarttime)->diffForHumans() ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $s->acctstoptime ? optional($s->acctstoptime)->diffForHumans() : 'Active' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 text-right">{{ $s->formatted_total_data_usage ?? '0 B' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-sm text-gray-500">No recent sessions for this router.</div>
            @endif
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

            // Note: recent sessions are rendered server-side for affiliates (read-only)
            // (no client-side injection required here)
        })();
    </script>
</x-app-layout>
