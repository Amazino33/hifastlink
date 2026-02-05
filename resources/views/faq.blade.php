<x-app-layout>
    <section class="py-20 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h1 class="text-5xl lg:text-6xl font-black text-white mb-4">
                    Frequently Asked <span class="text-blue-300">Questions</span>
                </h1>
                <p class="text-xl text-white/90 max-w-2xl mx-auto">
                    Find quick answers to the most common questions about our services.
                </p>
            </div>
        </div>
    </section>

    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <div class="space-y-4">
                {{-- General Questions --}}
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 mb-4">General Questions</h2>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        What is HiFastLink?
                    </h3>
                    <p class="text-gray-600 pl-9">HiFastLink is a high-speed satellite and wireless internet service provider offering reliable connectivity across Nigeria. We provide flexible data plans for homes, businesses, and mobile users.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        How fast can I get connected?
                    </h3>
                    <p class="text-gray-600 pl-9">Your plan activates immediately after payment confirmation. Simply connect to the network using your credentials and start browsing!</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        What areas do you cover?
                    </h3>
                    <p class="text-gray-600 pl-9">We provide coverage across major Nigerian cities including Lagos, Abuja, Port Harcourt, and more. Satellite coverage is available nationwide. Check our <a href="{{ route('coverage') }}" class="text-primary font-semibold hover:underline">coverage map</a> for details.</p>
                </div>

                {{-- Plans & Pricing --}}
                <div class="mt-12 mb-8">
                    <h2 class="text-2xl font-black text-gray-900 mb-4">Plans & Pricing</h2>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        What happens when my data runs out?
                    </h3>
                    <p class="text-gray-600 pl-9">You can easily renew or upgrade your plan from your dashboard. Your account remains active, and any unused validity days are preserved when you top up.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        Can I share my plan with family?
                    </h3>
                    <p class="text-gray-600 pl-9">Yes! Family plans allow multiple users to share the same data pool. Perfect for homes and small offices. The family admin manages members and monitors usage.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        Can I upgrade or downgrade my plan?
                    </h3>
                    <p class="text-gray-600 pl-9">Yes, you can change your plan anytime from your dashboard. When upgrading, you'll receive additional data immediately. Unused data from your previous plan rolls over based on your plan terms.</p>
                </div>

                {{-- Payment & Billing --}}
                <div class="mt-12 mb-8">
                    <h2 class="text-2xl font-black text-gray-900 mb-4">Payment & Billing</h2>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        What payment methods do you accept?
                    </h3>
                    <p class="text-gray-600 pl-9">We accept payments via Paystack (cards, bank transfer, USSD), voucher codes, and manual bank transfers. All transactions are secure and encrypted.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        Do you offer refunds?
                    </h3>
                    <p class="text-gray-600 pl-9">We offer a 24-hour satisfaction guarantee for new subscriptions. Contact support if you experience any issues with your connection during this period.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        Are there any hidden fees?
                    </h3>
                    <p class="text-gray-600 pl-9">No. The price you see is the price you pay. There are no installation fees, activation charges, or hidden costs.</p>
                </div>

                {{-- Technical Support --}}
                <div class="mt-12 mb-8">
                    <h2 class="text-2xl font-black text-gray-900 mb-4">Technical Support</h2>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        What do I need to connect?
                    </h3>
                    <p class="text-gray-600 pl-9">You need a Wi-Fi enabled device (smartphone, laptop, tablet) and your login credentials. For satellite plans, installation equipment will be provided.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        How do I check my data usage?
                    </h3>
                    <p class="text-gray-600 pl-9">Log into your dashboard to see real-time data usage, remaining balance, plan expiry date, and connection status. You can also track family member usage if you're a family admin.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fa-solid fa-circle-question text-primary mr-3"></i>
                        My internet is slow. What should I do?
                    </h3>
                    <p class="text-gray-600 pl-9">First, check your data balance and signal strength. Try moving closer to the router or restarting your device. If issues persist, contact our 24/7 support team for assistance.</p>
                </div>
            </div>

            {{-- Still have questions CTA --}}
            <div class="mt-16 bg-gradient-to-br from-primary to-secondary rounded-3xl p-12 text-center text-white">
                <h2 class="text-3xl font-black mb-4">Still Have Questions?</h2>
                <p class="text-xl text-white/90 mb-8">Can't find what you're looking for? We're here to help!</p>
                <a href="{{ route('contact') }}" class="inline-block bg-white text-primary font-bold px-8 py-4 rounded-full hover:bg-blue-50 transition-all duration-300 transform hover:scale-105">
                    Contact Support
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
