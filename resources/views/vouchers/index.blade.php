<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <i class="fa-solid fa-ticket text-blue-600"></i>
                Voucher Management & Financial Ledger
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                    Prepaid Financial Mode
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        topUpModal: false,
        topUpAmount: 5000,
        planId: '{{ $plans->first()?->id ?? '' }}',
        quantity: 10,
        plans: {{ $plans->toJson() }},
        get selectedPlan() {
            return this.plans.find(p => p.id == this.planId) || null;
        },
        get totalCost() {
            return (this.selectedPlan ? parseFloat(this.selectedPlan.price) : 0) * (parseInt(this.quantity) || 0);
        },
        get canAfford() {
            return {{ $walletBalance }} >= this.totalCost;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl p-4 flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-green-500 text-lg flex-shrink-0"></i>
                    <p class="text-green-800 dark:text-green-300 font-medium text-sm">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl p-4 flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-lg flex-shrink-0"></i>
                    <p class="text-red-800 dark:text-red-300 font-medium text-sm">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Financial Overview & Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Wallet Balance Card --}}
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 shadow-sm text-white flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-8xl">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-100">Prepaid Wallet</span>
                            <i class="fa-solid fa-shield-halved text-blue-200"></i>
                        </div>
                        <div class="text-3xl font-black tracking-tight">
                            ₦{{ number_format($walletBalance, 2) }}
                        </div>
                        <p class="text-xs text-blue-100 mt-1">Available for voucher generation</p>
                    </div>
                    <div class="mt-4">
                        <button type="button" @click="topUpModal = true"
                                class="w-full py-2 px-3 bg-white hover:bg-blue-50 text-blue-700 font-bold text-xs rounded-xl shadow transition-colors flex items-center justify-center gap-2">
                            <i class="fa-solid fa-plus-circle"></i> Top Up Wallet
                        </button>
                    </div>
                </div>

                {{-- Total Vouchers Created --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Vouchers</span>
                        <div class="text-3xl font-black text-gray-900 dark:text-white mt-2">
                            {{ $vouchers->total() }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-4 flex items-center gap-1.5">
                        <i class="fa-solid fa-barcode text-blue-500"></i> Lifetime generated tokens
                    </div>
                </div>

                {{-- Total Batches --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tracked Batches</span>
                        <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-2">
                            {{ $batches->count() }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-4 flex items-center gap-1.5">
                        <i class="fa-solid fa-boxes-stacked text-indigo-500"></i> Financial lot entries
                    </div>
                </div>

                {{-- Plan Count --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Active Plans</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                            {{ $plans->count() }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-4 flex items-center gap-1.5">
                        <i class="fa-solid fa-tags text-emerald-500"></i> Available retail tariffs
                    </div>
                </div>
            </div>

            {{-- Batch Generator Card (Financial Locked) --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-coins text-amber-500"></i> Generate Prepaid Voucher Batch
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Select a tariff and batch volume. The total face value is deducted from your prepaid wallet.
                        </p>
                    </div>
                    <span class="text-xs px-3 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-semibold rounded-full border border-green-200 dark:border-green-800">
                        Zero Financial Leakage
                    </span>
                </div>

                <div class="p-6">
                    <form action="{{ route('vouchers.generate') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            {{-- Plan Selection --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    Target Data Plan <span class="text-red-500">*</span>
                                </label>
                                <select name="plan_id" x-model="planId" required
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">
                                            {{ $plan->name }} — ₦{{ number_format($plan->price, 2) }} ({{ $plan->validity_days }}d)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Quantity Input --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    Number of Vouchers <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="quantity" min="1" max="100" x-model.number="quantity" required
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Router / Location --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    Assigned Router / Location
                                </label>
                                <select name="router_id"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">Global (All Routers)</option>
                                    @foreach($ownedRouters as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->location ?? 'Site' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Batch Label --}}
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    Batch Label / Remark <span class="font-normal text-gray-400">(Optional for bookkeeping)</span>
                                </label>
                                <input type="text" name="label" placeholder="e.g. Front Desk Scratch Cards, Cafe POS Stock" maxlength="100"
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        {{-- Live Financial Checkout Bar --}}
                        <div class="mt-6 p-4 rounded-xl border flex flex-col sm:flex-row items-center justify-between gap-4"
                             :class="canAfford ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : 'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800'">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg"
                                     :class="canAfford ? 'bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-300' : 'bg-red-100 dark:bg-red-800 text-red-600 dark:text-red-300'">
                                    <i :class="canAfford ? 'fa-solid fa-calculator' : 'fa-solid fa-triangle-exclamation'"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span>Total Batch Cost:</span>
                                        <span class="text-lg text-blue-600 dark:text-blue-400">₦<span x-text="totalCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span></span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="quantity"></span> voucher(s) × ₦<span x-text="(selectedPlan ? parseFloat(selectedPlan.price) : 0).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                                        · Your Balance: <strong class="text-gray-700 dark:text-gray-300">₦{{ number_format($walletBalance, 2) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <template x-if="canAfford">
                                    <button type="submit"
                                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-sm flex items-center gap-2">
                                        <i class="fa-solid fa-lock"></i> Pay from Wallet & Generate
                                    </button>
                                </template>
                                <template x-if="!canAfford">
                                    <button type="button" @click="topUpModal = true"
                                            class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl transition-all shadow-sm flex items-center gap-2">
                                        <i class="fa-solid fa-plus"></i> Insufficient Balance — Top Up Wallet
                                    </button>
                                </template>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Recent Voucher Batches --}}
            @if($batches->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-receipt text-indigo-500"></i> Recent Financial Batches
                    </h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $batches->count() }} batches</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Batch Code</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Plan</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Total Paid</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Redeemed</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Payment</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @foreach($batches as $b)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $b->batch_code }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $b->plan?->name ?? 'Custom' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $b->quantity }} codes</td>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">₦{{ number_format((float) $b->total_cost, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full font-semibold {{ $b->redeemed_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $b->redeemed_count }} / {{ $b->quantity }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 uppercase">
                                        {{ $b->payment_method }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-400">{{ $b->created_at->format('d M, H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Vouchers table with bulk actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                 x-data="{
                     selected: [],
                     allIds: {{ $vouchers->pluck('id')->toJson() }},
                     get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                     toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds]; },
                     toggle(id) {
                         this.selected.includes(id)
                             ? this.selected.splice(this.selected.indexOf(id), 1)
                             : this.selected.push(id);
                     }
                 }">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4 flex-wrap">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-ticket text-blue-600"></i> Active Vouchers Inventory
                    </h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $vouchers->total() }} total</span>
                </div>

                {{-- Bulk action bar (appears when items are selected) --}}
                <div x-show="selected.length > 0" x-transition
                     class="px-6 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800 flex items-center gap-4 flex-wrap">
                    <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">
                        <span x-text="selected.length"></span> selected
                    </span>

                    {{-- Bulk Revoke --}}
                    <form action="{{ route('vouchers.bulk-destroy') }}" method="POST"
                          @submit.prevent="if(confirm('Revoke ' + selected.length + ' voucher(s)? This cannot be undone.')) { $el.querySelectorAll('.bulk-id').forEach(el => el.remove()); selected.forEach(id => { const input = document.createElement('input'); input.type='hidden'; input.name='ids[]'; input.value=id; input.classList.add('bulk-id'); $el.appendChild(input); }); $el.submit(); }">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-colors">
                            <i class="fa-solid fa-trash"></i> Revoke Selected
                        </button>
                    </form>

                    {{-- Bulk Share via WhatsApp --}}
                    <button type="button"
                            @click="
                                let codes = [];
                                selected.forEach(id => {
                                    let row = document.querySelector('[data-id=\''+id+'\']');
                                    if (row) codes.push(row.dataset.code);
                                });
                                let text = 'Your WiFi voucher codes:\n\n' + codes.map((c, i) => (i+1) + '. ' + c).join('\n');
                                window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
                            "
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-lg transition-colors">
                        <i class="fa-brands fa-whatsapp"></i> Share Selected
                    </button>

                    {{-- Clear selection --}}
                    <button type="button" @click="selected = []"
                            class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 ml-auto">
                        Clear selection
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 w-8">
                                    <input type="checkbox"
                                           :checked="allSelected"
                                           @change="toggleAll()"
                                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan / Label</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Uses</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expires</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            @forelse($vouchers as $v)
                            @php
                                $expired   = $v->expires_at && $v->expires_at->isPast();
                                $redeemed  = !$expired && $v->used_count >= $v->max_uses;
                                if ($expired)      { $badge = ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',       'Expired'];  }
                                elseif ($redeemed) { $badge = ['bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',   'Redeemed']; }
                                else               { $badge = ['bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400', 'Active']; }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                :class="selected.includes({{ $v->id }}) ? 'bg-blue-50 dark:bg-blue-900/10' : ''"
                                data-id="{{ $v->id }}"
                                data-code="{{ $v->code }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           :checked="selected.includes({{ $v->id }})"
                                           @change="toggle({{ $v->id }})"
                                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    {{ $v->code }}
                                    @if($v->batch)
                                        <div class="text-[10px] text-gray-400 font-mono font-normal">{{ $v->batch->batch_code }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    @if($v->plan)
                                        <span class="font-medium text-gray-800 dark:text-white">{{ $v->plan->name }}</span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Custom</span>
                                    @endif
                                    @if($v->label)
                                        <br><span class="text-xs text-gray-400">{{ $v->label }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    ₦{{ number_format((float) ($v->price ?? $v->plan?->price ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    @if($v->is_unlimited)
                                        <span class="text-blue-600 dark:text-blue-400 font-semibold">Unlimited</span>
                                    @elseif($v->data_limit_mb)
                                        {{ $v->data_limit_mb >= 1024 ? round($v->data_limit_mb / 1024, 1).' GB' : $v->data_limit_mb.' MB' }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    {{ $v->used_count }} / {{ $v->max_uses }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                    @if($v->expires_at)
                                        {{ $v->expires_at->format('d M Y') }}
                                    @elseif($v->duration_hours)
                                        <span class="italic text-gray-400">{{ round($v->duration_hours / 24) }}d from use</span>
                                    @else
                                        No expiry
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge[0] }}">{{ $badge[1] }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <a href="https://wa.me/?text={{ urlencode('Your WiFi voucher code: ' . $v->code . ($v->label ? ' (' . $v->label . ')' : '')) }}"
                                           target="_blank"
                                           class="text-green-600 hover:text-green-800 dark:text-green-400 text-xs font-medium">
                                            <i class="fa-brands fa-whatsapp mr-1"></i>Share
                                        </a>
                                        <form action="{{ route('vouchers.destroy', $v->id) }}" method="POST"
                                              onsubmit="return confirm('Revoke this voucher?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 dark:text-red-400 text-xs font-medium">
                                                <i class="fa-solid fa-trash mr-1"></i>Revoke
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                    <i class="fa-solid fa-ticket text-3xl mb-3 block opacity-40"></i>
                                    No vouchers generated yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($vouchers->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $vouchers->links() }}
                    </div>
                @endif
            </div>

        </div>

        {{-- Top Up Wallet Modal --}}
        <div x-show="topUpModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700 relative"
                 @click.away="topUpModal = false">
                <button type="button" @click="topUpModal = false"
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">Top Up Prepaid Wallet</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Instant funding via Paystack (Cards & Bank Transfer)</p>
                    </div>
                </div>

                <form action="{{ route('wallet.topup') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Choose Amount (₦)
                        </label>
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            @foreach([2000, 5000, 10000, 20000, 50000, 100000] as $preset)
                                <button type="button" @click="topUpAmount = {{ $preset }}"
                                        :class="topUpAmount === {{ $preset }} ? 'bg-blue-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'"
                                        class="py-2 text-xs rounded-xl transition-colors">
                                    ₦{{ number_format($preset) }}
                                </button>
                            @endforeach
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-gray-400">₦</span>
                            <input type="number" name="amount" x-model.number="topUpAmount" min="500" max="500000" step="100" required
                                   class="w-full pl-8 rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Minimum: ₦500 · Maximum: ₦500,000</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow transition-colors flex items-center justify-center gap-2">
                            <i class="fa-solid fa-lock"></i> Pay with Paystack
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
