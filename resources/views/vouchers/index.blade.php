<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Family Voucher Management') }}
        </h2>

        @php
            // 1. Sync Math: Check Plan limit first, fallback to user limit, default to 10
            $planLimit = auth()->user()->plan->max_devices ?? null;
            $totalLimit = $planLimit ?? auth()->user()->family_limit ?? 10;

            // 2. Just count total vouchers created by this head
            $activeVouchers = \App\Models\Voucher::where('created_by', auth()->id())->count();

            // 3. Calculate remaining (Total - 1 Head - Active Vouchers)
            $maxGuestSlots = $totalLimit - 1;
            $slotsRemaining = max(0, $maxGuestSlots - $activeVouchers);
        @endphp

        <div class="flex justify-between items-center mb-6 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div>
                <h3 class="text-xl font-black text-gray-900">Invite Family & Friends</h3>
                <p class="text-sm text-gray-500">Your plan supports {{ $totalLimit }} total devices.</p>
            </div>
            <div class="text-right">
                <span class="block text-xs font-bold text-primary uppercase tracking-widest mb-1">Available Slots</span>
                <div class="flex items-baseline justify-end gap-1">
                    <span class="text-4xl font-black text-primary">{{ $slotsRemaining }}</span>
                    <span class="text-gray-400 font-bold text-lg">/ {{ $maxGuestSlots }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-6 bg-white shadow sm:rounded-lg border-l-4 border-primary">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Generate New Vouchers</h3>
                    <span class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold">
                        Plan: {{ auth()->user()->plan->name ?? 'Basic' }}
                        ({{ auth()->user()->plan->duration_hours ?? 24 }}hrs /
                        {{ auth()->user()->plan->max_devices ?? 1 }} Devices)
                    </span>
                </div>

                <form action="{{ route('vouchers.generate') }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700">How many vouchers do you need?</label>
                        
                        {{-- Smart Number Input: Caps at available slots --}}
                        <input type="number" 
                               name="quantity" 
                               min="1" 
                               max="{{ $slotsRemaining }}" 
                               value="{{ $slotsRemaining > 0 ? 1 : 0 }}"
                               @if($slotsRemaining <= 0) disabled @endif
                               class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-primary focus:ring-primary disabled:bg-gray-100 disabled:text-gray-500"
                               placeholder="Enter amount..."
                        >
                        @if($slotsRemaining > 0)
                            <p class="text-xs text-gray-500 mt-1">You can generate up to {{ $slotsRemaining }} vouchers.</p>
                        @endif
                    </div>
                    
                    {{-- Smart Button: Disables when 0 slots --}}
                    <button type="submit"
                        @if($slotsRemaining <= 0) disabled @endif
                        class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        Generate Now
                    </button>
                </form>

                <div class="mt-4">
                    @if(session('success'))
                        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm">
                            <div class="flex items-center">
                                <i class="fa-solid fa-check-circle text-green-500 mr-3 text-lg"></i>
                                <p class="text-green-800 font-bold">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
                            <div class="flex items-center">
                                <i class="fa-solid fa-triangle-exclamation text-red-500 mr-3 text-lg"></i>
                                <p class="text-red-800 font-bold">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Your Active Vouchers</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($vouchers as $voucher)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-blue-600">
                                            {{ $voucher->code }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $voucher->used_count }} / {{ $voucher->max_uses }} devices
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm flex gap-3">
                                            <a href="https://wa.me/?text=Your+WiFi+Voucher+Code+is:+{{ $voucher->code }}"
                                                target="_blank" class="text-green-600 hover:text-green-900">
                                                <i class="fa-brands fa-whatsapp mr-1"></i> Share
                                            </a>
                                            
                                            <form action="{{ route('vouchers.destroy', $voucher->id) }}" method="POST" onsubmit="return confirm('Delete this voucher and free up a slot?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fa-solid fa-trash mr-1"></i> Revoke
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">No vouchers generated yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $vouchers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>