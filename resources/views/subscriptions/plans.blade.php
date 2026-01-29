<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-3xl p-8">
                <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-6">Data Plans</h1>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($plans as $plan)
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-700 dark:to-gray-600 rounded-2xl p-6 {{ $plan->is_featured ? 'ring-4 ring-blue-500' : '' }}">
                        @if($plan->is_featured)
                        <div class="bg-blue-500 text-white text-xs font-bold px-3 py-1 rounded-full mb-4 inline-block">FEATURED</div>
                        @endif

                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $plan->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">{{ $plan->description }}</p>

                        <div class="text-3xl font-black text-blue-600 mb-2">{{ $plan->formatted_price }}</div>
                        <div class="text-sm text-gray-500 mb-4">{{ $plan->formatted_data_limit }} / {{ $plan->duration_days }} days</div>

                        <div class="text-xs text-gray-500 mb-4">{{ $plan->speed_limit }}</div>

                        <ul class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                            @foreach($plan->features ?? [] as $feature)
                            <li>• {{ $feature }}</li>
                            @endforeach
                        </ul>

                        <form method="POST" action="{{ route('subscription.subscribe', $plan) }}">
                            @csrf
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition-colors">
                                Subscribe Now
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>