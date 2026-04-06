{{-- resources/views/vouchers/success.blade.php --}}
<x-guest-layout>
    <div class="text-center py-10">
        <div class="mb-6 inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full">
            <i class="fa-solid fa-check text-4xl"></i>
        </div>
        <h2 class="text-3xl font-black text-gray-900 mb-2">Connected!</h2>
        <p class="text-gray-600 mb-8">
            Your voucher <strong>{{ session('voucher_code') }}</strong> has been activated.
        </p>
        
        <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl text-sm text-blue-800 mb-8 max-w-md mx-auto">
            You now have internet access. If you are at the shop and your device doesn't browse, please reconnect to the WiFi.
        </div>

        <a href="/" class="inline-block bg-primary text-white font-bold py-3 px-8 rounded-2xl shadow-lg hover:shadow-xl transition-all">
            Return Home
        </a>
    </div>
</x-guest-layout>