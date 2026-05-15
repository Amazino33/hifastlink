<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Router Breakdown — All Locations</x-slot>
        <x-slot name="description">Live stats per router, independent of the filter above</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 text-xs uppercase text-gray-500 dark:text-gray-400">
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">Router</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">Status</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap text-right">Online Now</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap text-right">Active Subs</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap text-right">Total Users</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap text-right">Today's Txns</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap text-right">Today's Revenue</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap text-right">Monthly Revenue</th>
                        <th class="py-3 font-semibold whitespace-nowrap">Last Seen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $row['location'] }}</div>
                            </td>
                            <td class="py-3 pr-4">
                                @if($row['is_online'])
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        Online
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Offline
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <span class="text-lg font-black text-gray-900 dark:text-white">{{ number_format($row['online_now']) }}</span>
                            </td>
                            <td class="py-3 pr-4 text-right font-semibold text-gray-800 dark:text-gray-200">
                                {{ number_format($row['active_subs']) }}
                            </td>
                            <td class="py-3 pr-4 text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($row['total_users']) }}
                            </td>
                            <td class="py-3 pr-4 text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($row['today_txns']) }}
                            </td>
                            <td class="py-3 pr-4 text-right font-semibold text-green-700 dark:text-green-400">
                                ₦{{ number_format($row['today_revenue'], 0) }}
                            </td>
                            <td class="py-3 pr-4 text-right font-semibold text-blue-700 dark:text-blue-400">
                                ₦{{ number_format($row['monthly_revenue'], 0) }}
                            </td>
                            <td class="py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $row['last_seen_at'] ? $row['last_seen_at']->diffForHumans() : 'Never' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                No active routers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-white/20 font-bold text-gray-900 dark:text-white">
                            <td class="pt-3 pr-4" colspan="2">Totals</td>
                            <td class="pt-3 pr-4 text-right">{{ number_format($rows->sum('online_now')) }}</td>
                            <td class="pt-3 pr-4 text-right">{{ number_format($rows->sum('active_subs')) }}</td>
                            <td class="pt-3 pr-4 text-right">{{ number_format($rows->sum('total_users')) }}</td>
                            <td class="pt-3 pr-4 text-right">{{ number_format($rows->sum('today_txns')) }}</td>
                            <td class="pt-3 pr-4 text-right text-green-700 dark:text-green-400">₦{{ number_format($rows->sum('today_revenue'), 0) }}</td>
                            <td class="pt-3 pr-4 text-right text-blue-700 dark:text-blue-400">₦{{ number_format($rows->sum('monthly_revenue'), 0) }}</td>
                            <td class="pt-3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
