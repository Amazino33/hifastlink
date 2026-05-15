<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Recent Sessions</x-slot>
        <x-slot name="description">Last 25 sessions — updates with the location filter</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 text-xs uppercase text-gray-500 dark:text-gray-400">
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">Status</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">User</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">Router</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">IP Address</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">MAC Address</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">Started</th>
                        <th class="py-3 pr-4 font-semibold whitespace-nowrap">Duration</th>
                        <th class="py-3 font-semibold whitespace-nowrap text-right">Data Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="py-3 pr-4">
                                @if($session['is_active'])
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Ended
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $session['username'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $session['user_name'] }}</div>
                            </td>
                            <td class="py-3 pr-4 text-gray-700 dark:text-gray-300">{{ $session['router_name'] }}</td>
                            <td class="py-3 pr-4 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $session['ip'] }}</td>
                            <td class="py-3 pr-4 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $session['mac'] }}</td>
                            <td class="py-3 pr-4">
                                <div class="text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $session['started_at'] ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $session['started_human'] ?? '' }}</div>
                            </td>
                            <td class="py-3 pr-4 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $session['duration'] }}</td>
                            <td class="py-3 text-right font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ $session['data_used'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                No sessions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
