<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Online Users</div>
        <div class="text-2xl font-bold">{{ $onlineUsers }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Today's Revenue</div>
        <div class="text-2xl font-bold">₦{{ number_format($todayRevenue, 0) }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Active Subscribers</div>
        <div class="text-2xl font-bold">{{ $activeSubscribers }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Total Data Usage</div>
        <div class="text-2xl font-bold">{{ $dataConsumed }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Total Users</div>
        <div class="text-2xl font-bold">{{ number_format($totalUsers) }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Today's Transactions</div>
        <div class="text-2xl font-bold">{{ $todayTransactions }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Monthly Revenue</div>
        <div class="text-2xl font-bold">₦{{ number_format($monthlyRevenue, 0) }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Current Router</div>
        <div class="text-2xl font-bold">{{ $currentRouter === 'all' ? 'All Locations' : $currentRouter }}</div>
    </div>
</div>

@if(!empty($recentSessions))
<div class="bg-white rounded shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Recent Sessions</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">User</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Duration</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach($recentSessions as $session)
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-900">{{ $session->username ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $session->username ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $session->acctstarttime ? \Carbon\Carbon::parse($session->acctstarttime)->format('M d, H:i') : '-' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $session->acctsessiontime ? gmdate('H:i:s', $session->acctsessiontime) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif