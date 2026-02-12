<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-4">
            <h1 class="text-2xl font-bold">Unmatched Transactions & Payments</h1>
            <p class="text-sm text-gray-500">Assign a router to transactions/payments that could not be auto-matched.</p>
        </div>

        @if(session('success'))
            <div class="mb-4 text-green-700 bg-green-50 p-3 rounded">{{ session('success') }}</div>
        @endif

        {{-- Bulk assign form for transactions --}}
        <form method="POST" action="{{ route('admin.router.assign') }}" class="mb-6">
            @csrf
            <input type="hidden" name="type" value="transaction">
            <div class="flex items-center gap-3 mb-2">
                <select name="router_id" class="p-2 border rounded">
                    <option value="">-- Select Router --</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Assign selected transactions</button>
            </div>

            <div class="overflow-x-auto bg-white shadow rounded">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2"><input type="checkbox" id="tx-select-all"></th>
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">User</th>
                            <th class="px-3 py-2">Amount</th>
                            <th class="px-3 py-2">Ref</th>
                            <th class="px-3 py-2">Created</th>
                            <th class="px-3 py-2">Nearby Sessions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($txs as $tx)
                            <tr>
                                <td class="px-3 py-2 text-center"><input type="checkbox" name="ids[]" value="{{ $tx->id }}"></td>
                                <td class="px-3 py-2">{{ $tx->id }}</td>
                                <td class="px-3 py-2">{{ optional($tx->user)->username ?? $tx->user_id }}</td>
                                <td class="px-3 py-2">{{ $tx->amount }}</td>
                                <td class="px-3 py-2">{{ $tx->reference }}</td>
                                <td class="px-3 py-2">{{ $tx->created_at }}</td>
                                <td class="px-3 py-2 text-xs text-gray-600">
                                    @if(!empty($txSessions[$tx->id]) && $txSessions[$tx->id]->isNotEmpty())
                                        @foreach($txSessions[$tx->id] as $s)
                                            <div class="mb-1">id: {{ $s->id }} — {{ $s->nasidentifier }} / {{ $s->nasipaddress }} @ {{ $s->acctstarttime }}</div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-400">No sessions</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $txs->appends(request()->except('txpage'))->links() }}
        </form>

        {{-- Bulk assign form for payments --}}
        <form method="POST" action="{{ route('admin.router.assign') }}" class="mb-6">
            @csrf
            <input type="hidden" name="type" value="payment">
            <div class="flex items-center gap-3 mb-2">
                <select name="router_id" class="p-2 border rounded">
                    <option value="">-- Select Router --</option>
                    @foreach($routers as $r)
                        <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->ip_address }})</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Assign selected payments</button>
            </div>

            <div class="overflow-x-auto bg-white shadow rounded">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2"><input type="checkbox" id="pay-select-all"></th>
                            <th class="px-3 py-2">ID</th>
                            <th class="px-3 py-2">User</th>
                            <th class="px-3 py-2">Amount</th>
                            <th class="px-3 py-2">Ref</th>
                            <th class="px-3 py-2">Created</th>
                            <th class="px-3 py-2">Nearby Sessions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($payments as $pay)
                            <tr>
                                <td class="px-3 py-2 text-center"><input type="checkbox" name="ids[]" value="{{ $pay->id }}"></td>
                                <td class="px-3 py-2">{{ $pay->id }}</td>
                                <td class="px-3 py-2">{{ optional($pay->user)->username ?? $pay->user_id }}</td>
                                <td class="px-3 py-2">{{ $pay->amount }}</td>
                                <td class="px-3 py-2">{{ $pay->reference }}</td>
                                <td class="px-3 py-2">{{ $pay->created_at }}</td>
                                <td class="px-3 py-2 text-xs text-gray-600">
                                    @if(!empty($paySessions[$pay->id]) && $paySessions[$pay->id]->isNotEmpty())
                                        @foreach($paySessions[$pay->id] as $s)
                                            <div class="mb-1">id: {{ $s->id }} — {{ $s->nasidentifier }} / {{ $s->nasipaddress }} @ {{ $s->acctstarttime }}</div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-400">No sessions</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $payments->appends(request()->except('paypage'))->links() }}
        </form>
    </div>

    <script>
        document.getElementById('tx-select-all')?.addEventListener('change', function(e){
            document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = e.target.checked);
        });
        document.getElementById('pay-select-all')?.addEventListener('change', function(e){
            document.querySelectorAll('form input[name="ids[]"]').forEach(cb => cb.checked = e.target.checked);
        });
    </script>
</x-app-layout>