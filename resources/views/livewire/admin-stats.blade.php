<div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Online Users</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $onlineUsers }}</div>
        </div>
    </div>

    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Revenue</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">₦{{ number_format($todayRevenue, 0) }}</div>
        </div>
    </div>

    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Subscribers</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $activeSubscribers }}</div>
        </div>
    </div>

    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Data Usage</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $dataConsumed }}</div>
        </div>
    </div>

    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ number_format($totalUsers) }}</div>
        </div>
    </div>

    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Today's Transactions</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $todayTransactions }}</div>
        </div>
    </div>

    <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Revenue</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">₦{{ number_format($monthlyRevenue, 0) }}</div>
        </div>
    </div>

    {{-- <div class="relative block h-full rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Router</div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $currentRouter === 'all' ? 'All Locations' : $currentRouter }}</div>
        </div>
    </div> --}}
</div>

@if(!empty($recentSessions))
<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recent Sessions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="divide-y divide-gray-200 dark:divide-white/5">
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
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