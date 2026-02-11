<x-app-layout>
    <div id="stats-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Online Users</div>
                <div id="stat-online-users" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">
                    {{ $stats['online_users'] ?? 0 }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Today's Revenue</div>
                <div id="stat-revenue" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">
                    ₦{{ number_format($stats['today_revenue'] ?? 0, 0) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active Subscribers</div>
                <div id="stat-subscribers" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">
                    {{ $stats['active_subscribers'] ?? 0 }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Data Usage</div>
                <div id="stat-data-usage" class="mt-2 text-3xl font-black text-gray-900 dark:text-white">
                    {{ $stats['data_consumed'] ?? '0 B' }}
                </div>
            </div>
        </div>

        @if(!empty($recent_sessions))
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Sessions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">User</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($recent_sessions as $session)
                                <tr>
                                    <td class="px-6 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $session->name ?? '-' }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $session->username ?? '-' }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $session->created_at ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <script>
        function fetchStats(routerId, btnElement) {
            // 1. Visual Feedback (Toggle Active State)
            document.querySelectorAll('.filter-chip').forEach(el => {
                el.classList.remove('active', 'btn-primary');
                el.classList.add('btn-outline-secondary');
            });

            // Highlight the clicked button
            if (btnElement) {
                btnElement.classList.add('active', 'btn-primary');
                btnElement.classList.remove('btn-outline-secondary');
            }

            // 2. Show Loading State
            const container = document.getElementById('stats-container');
            if (container) container.style.opacity = '0.5';

            // 3. Fetch Data via API
            fetch(`{{ route('api.admin.stats') }}?router_id=${routerId}`)
                .then(response => response.json())
                .then(data => {
                    // 4. Update Numbers (with formatting)
                    if (document.getElementById('stat-online-users'))
                        document.getElementById('stat-online-users').innerText = data.online_users;

                    if (document.getElementById('stat-revenue'))
                        document.getElementById('stat-revenue').innerText = '₦' + new Intl.NumberFormat().format(data.today_revenue);

                    if (document.getElementById('stat-subscribers'))
                        document.getElementById('stat-subscribers').innerText = data.active_subscribers;

                    if (document.getElementById('stat-data-usage'))
                        document.getElementById('stat-data-usage').innerText = data.data_consumed;

                    if (container) container.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Error fetching stats:', error);
                    if (container) container.style.opacity = '1';
                });
        }
    </script>
</x-app-layout>
