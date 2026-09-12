<x-guest-layout>
    <div class="text-center py-8">
        <i class="fa-solid fa-spinner fa-spin text-3xl text-primary mb-3"></i>
        <p class="text-gray-600 text-sm">Redirecting to verified sign-up...</p>
    </div>
    <script>
        window.location.href = "{{ route('login', array_merge(['tab' => 'whatsapp'], request()->all())) }}";
    </script>
</x-guest-layout>
