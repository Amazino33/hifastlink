<x-app-layout>
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl lg:text-6xl font-black text-white mb-4">
                    Help <span class="text-blue-300">Center</span>
                </h1>
                <p class="text-xl text-white/90 max-w-2xl mx-auto">
                    Find answers to common questions and get the support you need.
                </p>
            </div>
        </div>
    </section>

    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            {{-- Quick Help Cards --}}
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
                <a href="{{ route('faq') }}" class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-circle-question text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">FAQs</h3>
                    <p class="text-gray-600 text-sm">Frequently asked questions</p>
                </a>

                <a href="{{ route('installation') }}" class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-screwdriver-wrench text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Installation</h3>
                    <p class="text-gray-600 text-sm">Setup guides & tutorials</p>
                </a>

                <a href="{{ route('status') }}" class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-signal text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Network Status</h3>
                    <p class="text-gray-600 text-sm">Check service status</p>
                </a>

                <a href="{{ route('contact') }}" class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transform hover:-translate-y-2 transition-all duration-300 text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-headset text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Contact Support</h3>
                    <p class="text-gray-600 text-sm">Get personalized help</p>
                </a>
            </div>

            {{-- Common Topics --}}
            <div class="mb-16">
                <h2 class="text-3xl font-black text-gray-900 mb-8 text-center">Common Topics</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl p-6 shadow-md">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-wifi text-primary mr-3"></i>
                            Connection Issues
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Unable to connect to the network</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Slow internet speeds</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Intermittent disconnections</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-md">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-credit-card text-green-600 mr-3"></i>
                            Billing & Payments
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>How to renew my subscription</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Payment methods accepted</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Refund policy</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-md">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-user-circle text-blue-600 mr-3"></i>
                            Account Management
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Reset password</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Update profile information</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Check data usage</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-md">
                        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fa-solid fa-users text-purple-600 mr-3"></i>
                            Family Plans
                        </h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Add family members</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Manage shared data</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fa-solid fa-chevron-right text-primary text-xs mt-1 mr-2"></i>
                                <span>Remove family members</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Contact Support CTA --}}
            <div class="bg-gradient-to-br from-primary to-secondary rounded-3xl p-12 text-center text-white">
                <h2 class="text-3xl font-black mb-4">Still Need Help?</h2>
                <p class="text-xl text-white/90 mb-8">Our support team is available 24/7 to assist you</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('contact') }}" class="bg-white text-primary font-bold px-8 py-4 rounded-full hover:bg-blue-50 transition-all duration-300 transform hover:scale-105">
                        <i class="fa-solid fa-envelope mr-2"></i>
                        Email Support
                    </a>
                    <a href="tel:+2348001234567" class="bg-white/20 border-2 border-white text-white font-bold px-8 py-4 rounded-full hover:bg-white/30 transition-all duration-300 transform hover:scale-105">
                        <i class="fa-solid fa-phone mr-2"></i>
                        Call Us
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
