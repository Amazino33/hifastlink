<div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Online Users</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $onlineUsers }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Revenue</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">₦{{ number_format($todayRevenue, 0) }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscribers</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $activeSubscribers }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Data Usage</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $dataConsumed }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalUsers) }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Transactions</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $todayTransactions }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Revenue</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">₦{{ number_format($monthlyRevenue, 0) }}</div>
    </div>

    <div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Router</div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $currentRouter === 'all' ? 'All Locations' : $currentRouter }}</div>
    </div>
</div>

@if(!empty($recentSessions))
<div class="filament-card rounded-lg bg-white shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Sessions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($recentSessions as $session)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $session->username ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $session->acctstarttime ? \Carbon\Carbon::parse($session->acctstarttime)->format('M d, H:i') : '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">{{ $session->acctsessiontime ? gmdate('H:i:s', $session->acctsessiontime) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
</div>