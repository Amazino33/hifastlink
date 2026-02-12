<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Online Users</div>
        <div id="filament-stat-online-users" class="text-2xl font-bold">{{ $onlineUsers }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Today's Revenue</div>
        <div id="filament-stat-revenue" class="text-2xl font-bold">₦{{ number_format($todayRevenue, 0) }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Active Subscribers</div>
        <div id="filament-stat-subscribers" class="text-2xl font-bold">{{ $activeSubscribers }}</div>
    </div>

    <div class="bg-white p-4 rounded shadow">
        <div class="text-xs text-gray-500">Total Data Usage</div>
        <div id="filament-stat-data-usage" class="text-2xl font-bold">{{ $dataConsumed }}</div>
    </div>
</div>

<script>
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
</script>