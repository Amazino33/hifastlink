<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Family Voucher Management') }}
        </h2>

        @php
            $max = auth()->user()->plan->family_limit ?? 1;
            $activeCount = $vouchers->where('is_used', false)->count();
            $left = $max - 1 - $activeCount;
        @endphp

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Invite Guests</h3>
            <div class="text-right">
                <span class="block text-xs font-bold text-gray-500 uppercase">Devices Remaining</span>
                <span class="text-2xl font-black text-primary">{{ max(0, $left) }}</span>
                <span class="text-gray-400">/ {{ $max - 1 }}</span>
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
                        <select name="quantity"
                            class="mt-1 block w-full rounded-md border-gray-200 shadow-sm focus:border-primary focus:ring-primary">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}">{{ $i }} Voucher{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit"
                        class="bg-primary hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-300">
                        Generate Now
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Your Active Vouchers</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($vouchers as $voucher)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-blue-600">
                                            {{ $voucher->code }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $voucher->is_used ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $voucher->is_used ? 'Used' : 'Active' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $voucher->used_count }} / {{ $voucher->max_uses }} devices
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="https://wa.me/?text=Your+WiFi+Voucher+Code+is:+{{ $voucher->code }}"
                                                target="_blank" class="text-green-600 hover:text-green-900">
                                                <i class="fa-brands fa-whatsapp mr-1"></i> Share
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">No vouchers generated
                                            yet.</td>
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