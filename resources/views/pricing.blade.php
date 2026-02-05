<x-app-layout>
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl lg:text-6xl font-black text-white mb-4">
                    Choose Your <span class="text-blue-300">Perfect Plan</span>
                </h1>
                <p class="text-xl text-white/90 max-w-2xl mx-auto">
                    Flexible pricing options for every need. All plans include ultra-fast speeds and reliable connectivity.
                </p>
            </div>

            @foreach($plansByDuration as $days => $plans)
                <div class="mb-16">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-black text-white mb-2">
                            {{ $days }} {{ $days == 1 ? 'Day' : 'Days' }} Plans
                        </h2>
                        <div class="w-32 h-1 bg-blue-300 mx-auto rounded-full"></div>
                    </div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($plans as $plan)
                            <div class="bg-white rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden group">
                                <!-- Plan Header -->
                                <div class="bg-gradient-to-br from-primary to-secondary p-6 text-white">
                                    <h3 class="text-2xl font-bold mb-2">{{ $plan->name }}</h3>
                                    <div class="text-4xl font-black mb-1">₦{{ number_format($plan->price, 0) }}</div>
                                    <div class="text-sm text-white/80">{{ $plan->validity_days }} {{ $plan->validity_days == 1 ? 'day' : 'days' }} validity</div>
                                </div>

                                <!-- Plan Features -->
                                <div class="p-6">
                                    <ul class="space-y-3 mb-6">
                                        <!-- Data Limit -->
                                        <li class="flex items-center space-x-3">
                                            <i class="fa-solid fa-database text-primary"></i>
                                            <span class="font-semibold">
                                                @if($plan->limit_unit === 'Unlimited')
                                                    Unlimited Data
                                                @else
                                                    {{ $plan->data_limit }} {{ $plan->limit_unit }} Data
                                                @endif
                                            </span>
                                        </li>

                                        <!-- Speed -->
                                        @if($plan->speed_limit_download || $plan->speed_limit_upload)
                                            <li class="flex items-center space-x-3">
                                                <i class="fa-solid fa-gauge-high text-primary"></i>
                                                <span>{{ $plan->speed_limit_download }}k / {{ $plan->speed_limit_upload }}k</span>
                                            </li>
                                        @endif

                                        <!-- Family Plan -->
                                        @if($plan->is_family)
                                            <li class="flex items-center space-x-3">
                                                <i class="fa-solid fa-users text-primary"></i>
                                                <span>Up to {{ $plan->family_limit }} devices</span>
                                            </li>
                                        @endif

                                        <!-- Always Available -->
                                        <li class="flex items-center space-x-3">
                                            <i class="fa-solid fa-circle-check text-green-500"></i>
                                            <span>24/7 Support</span>
                                        </li>
                                        <li class="flex items-center space-x-3">
                                            <i class="fa-solid fa-circle-check text-green-500"></i>
                                            <span>Instant Activation</span>
                                        </li>
                                    </ul>

                                    <!-- Subscribe Button -->
                                    @auth
                                        <form action="{{ route('pay') }}" method="POST" class="w-full">
                                            @csrf
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit" class="w-full bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg transform group-hover:scale-105 transition-all duration-300">
                                                Subscribe Now
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('login') }}" 
                                           class="block w-full bg-primary hover:bg-secondary text-white font-bold py-3 rounded-lg text-center transform group-hover:scale-105 transition-all duration-300">
                                            Login to Subscribe
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-black text-gray-900 mb-4">Frequently Asked Questions</h2>
                <div class="w-24 h-1 bg-primary mx-auto rounded-full"></div>
            </div>

            <div class="space-y-4">
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">How fast can I get connected?</h3>
                    <p class="text-gray-600">Your plan activates immediately after payment confirmation. Simply connect to the router and start browsing!</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">What happens when my data runs out?</h3>
                    <p class="text-gray-600">You can easily renew or upgrade your plan from your dashboard. Any unused validity days are preserved.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Can I share my plan with family?</h3>
                    <p class="text-gray-600">Yes! Family plans allow multiple users to share the same data pool. Perfect for homes and small offices.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Do you offer refunds?</h3>
                    <p class="text-gray-600">We offer a 24-hour satisfaction guarantee. Contact support if you experience any issues with your connection.</p>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
