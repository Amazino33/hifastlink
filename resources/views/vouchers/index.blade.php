<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Voucher Management
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Stats bar --}}
            @php
                $totalLimit      = auth()->user()->plan->family_limit ?? auth()->user()->family_limit ?? 10;
                $activeCount     = \App\Models\Voucher::where('created_by', auth()->id())->count();
                $usedSlots       = $totalLimit - 1;
                $remaining       = max(0, $usedSlots - $activeCount);
                $canCustomCreate = $isAdmin || $isFamilyAdmin;
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['label' => 'Total Vouchers',  'value' => $activeCount,       'color' => 'blue'],
                    ['label' => 'Slots Used',       'value' => $activeCount,       'color' => 'orange'],
                    ['label' => 'Slots Available',  'value' => $isAdmin ? '∞' : $remaining, 'color' => 'green'],
                    ['label' => 'Plan Limit',       'value' => $isAdmin ? 'Unlimited' : $usedSlots, 'color' => 'purple'],
                ] as $stat)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-2xl font-black text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400">{{ $stat['value'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>

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

            {{-- Create panel --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                 x-data="{ tab: 'quick' }">

                {{-- Tabs --}}
                <div class="flex border-b border-gray-100 dark:border-gray-700">
                    <button @click="tab = 'quick'"
                        :class="tab === 'quick'
                            ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 font-bold'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex-1 py-4 text-sm transition-colors">
                        <i class="fa-solid fa-bolt mr-2"></i>Quick Create
                    </button>
                    @if($canCustomCreate)
                    <button @click="tab = 'custom'"
                        :class="tab === 'custom'
                            ? 'border-b-2 border-purple-600 text-purple-600 dark:text-purple-400 font-bold'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex-1 py-4 text-sm transition-colors">
                        <i class="fa-solid fa-sliders mr-2"></i>Custom Create
                    </button>
                    @endif
                </div>

                {{-- Quick tab --}}
                <div x-show="tab === 'quick'" x-transition class="p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Generates vouchers that inherit your current plan's settings (duration, data, speed).
                    </p>
                    <form action="{{ route('vouchers.generate') }}" method="POST" class="flex flex-wrap items-end gap-4">
                        @csrf
                        <input type="hidden" name="mode" value="quick">
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                Quantity
                            </label>
                            <input type="number" name="quantity" min="1"
                                   max="{{ $isAdmin ? 100 : max(0, $remaining) }}"
                                   value="1"
                                   @if(!$isAdmin && $remaining <= 0) disabled @endif
                                   class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                            @if(!$isAdmin)
                                <p class="text-xs text-gray-400 mt-1">{{ $remaining }} slot(s) available</p>
                            @endif
                        </div>
                        <button type="submit"
                                @if(!$isAdmin && $remaining <= 0) disabled @endif
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                            <i class="fa-solid fa-plus mr-2"></i>Generate
                        </button>
                    </form>
                </div>

                {{-- Custom tab --}}
                @if($canCustomCreate)
                <div x-show="tab === 'custom'" x-transition class="p-6"
                     x-data="{
                         isUnlimited: {{ (!$isAdmin && $planLimits && $planLimits['is_unlimited']) ? 'false' : 'false' }},
                         dataUnit: 'MB',
                         hasSpeed: false,
                         hasPlan: false
                     }">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Set your own validity, data cap, speed limits, and number of uses. Perfect for welcome gifts, trials, or one-off access.
                    </p>

                    {{-- Plan limits notice for non-admins --}}
                    @if(!$isAdmin && $planLimits)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <i class="fa-solid fa-circle-info text-blue-500"></i>
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">
                                Your Plan Limits — {{ $planLimits['plan_name'] }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Max Validity</span>
                                <span class="font-semibold text-gray-800 dark:text-white">{{ $planLimits['validity_days'] }} days</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Data Cap</span>
                                <span class="font-semibold text-gray-800 dark:text-white">{{ $planLimits['data_human'] }}</span>
                            </div>
                            @if($planLimits['speed_limit_download'])
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Max Download</span>
                                <span class="font-semibold text-gray-800 dark:text-white">{{ $planLimits['speed_limit_download'] }} Kbps</span>
                            </div>
                            @endif
                            @if($planLimits['speed_limit_upload'])
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 block">Max Upload</span>
                                <span class="font-semibold text-gray-800 dark:text-white">{{ $planLimits['speed_limit_upload'] }} Kbps</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('vouchers.generate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mode" value="custom">
                        <input type="hidden" name="is_unlimited" :value="isUnlimited ? '1' : '0'">

                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">

                            {{-- Label / Note --}}
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Label <span class="font-normal text-gray-400">(optional — helps you identify this batch)</span>
                                </label>
                                <input type="text" name="label" maxlength="100"
                                       placeholder="e.g. 3-day welcome gift, Event promo, Staff access..."
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>

                            {{-- Validity --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Validity
                                </label>
                                <div class="relative">
                                    <input type="number" name="validity_days" min="1"
                                           max="{{ $isAdmin ? 365 : ($planLimits['validity_days'] ?? 365) }}"
                                           value="{{ $isAdmin ? 3 : min(3, $planLimits['validity_days'] ?? 3) }}" required
                                           class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 pr-14">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 font-medium">days</span>
                                </div>
                            </div>

                            {{-- Max uses --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Uses per Code
                                </label>
                                <input type="number" name="max_uses" min="1" max="500" value="1" required
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                <p class="text-xs text-gray-400 mt-1">How many devices can use a single code</p>
                            </div>

                            {{-- Quantity --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Number of Codes
                                </label>
                                <input type="number" name="quantity" min="1" max="100" value="1" required
                                       class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>

                            {{-- Data allowance --}}
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">
                                    Data Allowance
                                </label>
                                <div class="flex items-center gap-3">
                                    {{-- Unlimited toggle --}}
                                    @if($isAdmin || ($planLimits && $planLimits['is_unlimited']))
                                    <label class="flex items-center gap-2 cursor-pointer select-none flex-shrink-0">
                                        <div class="relative">
                                            <input type="checkbox" class="sr-only" x-model="isUnlimited">
                                            <div :class="isUnlimited ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600'"
                                                 class="w-10 h-5 rounded-full transition-colors"></div>
                                            <div :class="isUnlimited ? 'translate-x-5' : 'translate-x-0'"
                                                 class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Unlimited</span>
                                    </label>
                                    @else
                                    <label class="flex items-center gap-2 opacity-40 cursor-not-allowed select-none flex-shrink-0" title="Your plan has a data cap — unlimited vouchers not available">
                                        <div class="relative">
                                            <input type="checkbox" class="sr-only" disabled>
                                            <div class="w-10 h-5 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-500">Unlimited</span>
                                        <span class="text-xs text-gray-400">(not on your plan)</span>
                                    </label>
                                    @endif

                                    <div x-show="!isUnlimited" class="flex flex-1 gap-2">
                                        <input type="number" name="data_limit_mb" min="1"
                                               @if(!$isAdmin && $planLimits && $planLimits['data_limit_mb']) max="{{ $planLimits['data_limit_mb'] }}" @endif
                                               placeholder="e.g. 500"
                                               :required="!isUnlimited"
                                               class="flex-1 rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                        <select name="data_unit" x-model="dataUnit"
                                                class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                            <option value="MB">MB</option>
                                            <option value="GB">GB</option>
                                        </select>
                                    </div>
                                    <span x-show="isUnlimited" class="text-sm text-purple-600 dark:text-purple-400 font-semibold">No data cap</span>
                                </div>
                            </div>

                            {{-- Speed limits (optional) --}}
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="flex items-center gap-2 cursor-pointer select-none mb-3">
                                    <div class="relative">
                                        <input type="checkbox" class="sr-only" x-model="hasSpeed">
                                        <div :class="hasSpeed ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600'"
                                             class="w-10 h-5 rounded-full transition-colors"></div>
                                        <div :class="hasSpeed ? 'translate-x-5' : 'translate-x-0'"
                                             class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Set Speed Limits</span>
                                    <span class="text-xs text-gray-400">(leave off to use plan defaults)</span>
                                </label>

                                <div x-show="hasSpeed" class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Download Speed</label>
                                        <div class="relative">
                                            <input type="number" name="speed_limit_download" min="0" placeholder="e.g. 2048"
                                                   @if(!$isAdmin && $planLimits && $planLimits['speed_limit_download']) max="{{ $planLimits['speed_limit_download'] }}" @endif
                                                   class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 pr-14">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">Kbps</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Upload Speed</label>
                                        <div class="relative">
                                            <input type="number" name="speed_limit_upload" min="0" placeholder="e.g. 512"
                                                   @if(!$isAdmin && $planLimits && $planLimits['speed_limit_upload']) max="{{ $planLimits['speed_limit_upload'] }}" @endif
                                                   class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500 pr-14">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">Kbps</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Attach to plan (optional) --}}
                            @if($plans->isNotEmpty())
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="flex items-center gap-2 cursor-pointer select-none mb-3">
                                    <div class="relative">
                                        <input type="checkbox" class="sr-only" x-model="hasPlan">
                                        <div :class="hasPlan ? 'bg-purple-600' : 'bg-gray-300 dark:bg-gray-600'"
                                             class="w-10 h-5 rounded-full transition-colors"></div>
                                        <div :class="hasPlan ? 'translate-x-5' : 'translate-x-0'"
                                             class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Attach to a Plan</span>
                                    <span class="text-xs text-gray-400">(optional — lets logged-in users activate the plan via this voucher)</span>
                                </label>
                                <div x-show="hasPlan">
                                    <select name="plan_id"
                                            class="w-full rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                        <option value="">— Select a plan —</option>
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}">
                                                {{ $plan->name }} — {{ $plan->validity_days }} days
                                                / {{ $plan->limit_unit === 'Unlimited' ? 'Unlimited' : $plan->data_limit . ' ' . $plan->limit_unit }}
                                                (₦{{ number_format($plan->price) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                    class="px-8 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition-colors shadow-sm">
                                <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Create Custom Vouchers
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>

            {{-- Vouchers table --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white">Your Vouchers</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $vouchers->total() }} total</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Label / Plan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Speed</th>
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
                                $active    = !$expired && !$redeemed;
                                if ($expired)        { $badge = ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',      'Expired'];  }
                                elseif ($redeemed)   { $badge = ['bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',   'Redeemed']; }
                                else                 { $badge = ['bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400', 'Active']; }
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                    {{ $v->code }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    @if($v->label)
                                        <span class="font-medium text-gray-800 dark:text-white">{{ $v->label }}</span>
                                        @if($v->plan)
                                            <br><span class="text-xs text-gray-400">{{ $v->plan->name }}</span>
                                        @endif
                                    @elseif($v->plan)
                                        {{ $v->plan->name }}
                                    @else
                                        <span class="text-xs text-gray-400 italic">Custom</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    @if($v->is_unlimited)
                                        <span class="text-purple-600 dark:text-purple-400 font-semibold">Unlimited</span>
                                    @elseif($v->data_limit_mb)
                                        {{ $v->data_limit_mb >= 1024
                                            ? round($v->data_limit_mb / 1024, 1) . ' GB'
                                            : $v->data_limit_mb . ' MB' }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap text-xs">
                                    @if($v->speed_limit_download || $v->speed_limit_upload)
                                        ↓{{ $v->speed_limit_download ?? '?' }}k / ↑{{ $v->speed_limit_upload ?? '?' }}k
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
                                        @php $days = round($v->duration_hours / 24); @endphp
                                        <span class="italic text-gray-400">{{ $days }}d from use</span>
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
                                           class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 text-xs font-medium">
                                            <i class="fa-brands fa-whatsapp mr-1"></i>Share
                                        </a>
                                        <form action="{{ route('vouchers.destroy', $v->id) }}" method="POST"
                                              onsubmit="return confirm('Revoke this voucher?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200 text-xs font-medium">
                                                <i class="fa-solid fa-trash mr-1"></i>Revoke
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                    <i class="fa-solid fa-ticket text-3xl mb-3 block opacity-40"></i>
                                    No vouchers yet. Create one above.
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
    </div>
</x-app-layout>
