<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
</div>