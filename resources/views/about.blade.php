<x-app-layout>
    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-gray-900 via-primary to-secondary py-40 px-6 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        </div>
        
        <div class="max-w-6xl mx-auto text-center relative z-10">
            <div class="inline-block mb-4 px-6 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white text-sm font-semibold uppercase tracking-wider">
                Our Story
            </div>
            <h1 class="text-5xl lg:text-7xl font-black text-white mb-6 leading-tight">
                Bridging the Digital Divide<br>Across Nigeria
            </h1>
            <p class="text-xl lg:text-2xl text-white/90 max-w-3xl mx-auto font-light">
                We're not just an internet provider. We're on a mission to connect every Nigerian to the opportunities of the digital world.
            </p>
        </div>
    </section>

    {{-- Story Section --}}
    <section class="py-24 px-6 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center mb-20">
                <div>
                    <div class="inline-block mb-6 px-4 py-2 bg-primary/10 rounded-lg text-primary text-sm font-bold uppercase tracking-wide">
                        Who We Are
                    </div>
                    <h2 class="text-4xl lg:text-5xl font-black text-gray-900 mb-6 leading-tight">
                        Built for Nigeria, By Nigerians
                    </h2>
                    <div class="space-y-6 text-lg text-gray-700 leading-relaxed">
                        <p>
                            HiFastLink was born from frustration—the kind that comes from watching businesses lose deals because of dropped connections, students missing online classes due to network failures, and families unable to connect with loved ones abroad. We knew Nigeria deserved better.
                        </p>
                        <p>
                            In 2023, a team of Nigerian engineers and entrepreneurs came together with a bold vision: to leverage satellite technology to deliver truly reliable internet across every corner of our nation. We weren't interested in making the same old promises. We were committed to building something that actually works.
                        </p>
                        <p>
                            Today, HiFastLink serves thousands of homes, businesses, and institutions from Lagos to Maiduguri, from Port Harcourt to Sokoto. Our satellite-powered network doesn't depend on crumbling infrastructure or unreliable terrestrial connections. It delivers consistent speeds up to 80 Mbps—day in, day out—no matter where you are in Nigeria.
                        </p>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="bg-gradient-to-br from-primary to-secondary p-12 rounded-3xl shadow-2xl text-white">
                        <div class="space-y-10">
                            <div class="border-b border-white/20 pb-8">
                                <div class="text-6xl font-black mb-2">2023</div>
                                <div class="text-lg font-light">Year Founded</div>
                            </div>
                            <div class="border-b border-white/20 pb-8">
                                <div class="text-6xl font-black mb-2">15</div>
                                <div class="text-lg font-light">States Covered</div>
                            </div>
                            <div class="pb-2">
                                <div class="text-6xl font-black mb-2">10K+</div>
                                <div class="text-lg font-light">Happy Customers</div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 -right-6 w-full h-full bg-primary/20 rounded-3xl -z-10"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mission & Values --}}
    <section class="py-24 px-6 bg-gray-50">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <div class="inline-block mb-4 px-4 py-2 bg-primary/10 rounded-lg text-primary text-sm font-bold uppercase tracking-wide">
                    What Drives Us
                </div>
                <h2 class="text-4xl lg:text-5xl font-black text-gray-900">
                    Our Mission & Values
                </h2>
            </div>

            <div class="grid lg:grid-cols-2 gap-8 mb-12">
                <div class="bg-white p-10 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-bullseye text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-4">Our Mission</h3>
                    <p class="text-lg text-gray-700 leading-relaxed">
                        To democratize internet access across Nigeria by providing fast, reliable, and affordable connectivity that empowers individuals, businesses, and communities to thrive—regardless of their location or circumstances.
                    </p>
                </div>

                <div class="bg-white p-10 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-secondary rounded-xl flex items-center justify-center mb-6">
                        <i class="fa-solid fa-eye text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-4">Our Vision</h3>
                    <p class="text-lg text-gray-700 leading-relaxed">
                        To become Africa's most trusted internet provider, known for unwavering reliability, cutting-edge technology, and a genuine commitment to bridging the digital divide across the continent.
                    </p>
                </div>
            </div>

            <div class="grid md:grid-cols-4 gap-6">
                <div class="bg-white p-8 rounded-xl text-center hover:shadow-xl transition-shadow">
                    <div class="text-4xl mb-4">🛡️</div>
                    <h4 class="font-bold text-gray-900 mb-2">Reliability</h4>
                    <p class="text-sm text-gray-600">Always on, always working</p>
                </div>
                <div class="bg-white p-8 rounded-xl text-center hover:shadow-xl transition-shadow">
                    <div class="text-4xl mb-4">💡</div>
                    <h4 class="font-bold text-gray-900 mb-2">Innovation</h4>
                    <p class="text-sm text-gray-600">Pioneering new solutions</p>
                </div>
                <div class="bg-white p-8 rounded-xl text-center hover:shadow-xl transition-shadow">
                    <div class="text-4xl mb-4">🤝</div>
                    <h4 class="font-bold text-gray-900 mb-2">Integrity</h4>
                    <p class="text-sm text-gray-600">Honest and transparent</p>
                </div>
                <div class="bg-white p-8 rounded-xl text-center hover:shadow-xl transition-shadow">
                    <div class="text-4xl mb-4">🌍</div>
                    <h4 class="font-bold text-gray-900 mb-2">Inclusivity</h4>
                    <p class="text-sm text-gray-600">Internet for everyone</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Why We're Different --}}
    <section class="py-24 px-6 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <div class="inline-block mb-4 px-4 py-2 bg-primary/10 rounded-lg text-primary text-sm font-bold uppercase tracking-wide">
                    The HiFastLink Difference
                </div>
                <h2 class="text-4xl lg:text-5xl font-black text-gray-900 mb-6">
                    Why Choose Us
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    We're not just another ISP. Here's what makes us stand out in the Nigerian market.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="group p-8 rounded-2xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all">
                    <div class="w-14 h-14 bg-primary/10 rounded-lg flex items-center justify-center mb-6 group-hover:bg-primary group-hover:scale-110 transition-all">
                        <i class="fa-solid fa-satellite text-2xl text-primary group-hover:text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Satellite Technology</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Advanced satellite infrastructure that bypasses traditional network limitations for true nationwide coverage.
                    </p>
                </div>

                <div class="group p-8 rounded-2xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all">
                    <div class="w-14 h-14 bg-primary/10 rounded-lg flex items-center justify-center mb-6 group-hover:bg-primary group-hover:scale-110 transition-all">
                        <i class="fa-solid fa-bolt text-2xl text-primary group-hover:text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Lightning Fast</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Consistent speeds up to 80 Mbps that don't slow down during peak hours or bad weather.
                    </p>
                </div>

                <div class="group p-8 rounded-2xl border-2 border-gray-200 hover:border-primary hover:shadow-xl transition-all">
                    <div class="w-14 h-14 bg-primary/10 rounded-lg flex items-center justify-center mb-6 group-hover:bg-primary group-hover:scale-110 transition-all">
                        <i class="fa-solid fa-headset text-2xl text-primary group-hover:text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Local Support</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Nigerian team providing 24/7 support in English, Pidgin, Yoruba, Igbo, and Hausa.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-24 px-6 bg-gradient-to-br from-primary to-secondary">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h2 class="text-4xl lg:text-6xl font-black mb-6">
                Ready to Experience Real Internet?
            </h2>
            <p class="text-xl lg:text-2xl mb-10 text-white/90">
                Join thousands of Nigerians who've already made the switch to reliable connectivity
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button class="bg-white text-primary font-bold text-lg py-5 px-10 rounded-full hover:bg-gray-100 hover:shadow-2xl transform hover:scale-105 transition-all">
                    Get Started Now
                </button>
                <button class="bg-transparent border-2 border-white text-white font-bold text-lg py-5 px-10 rounded-full hover:bg-white hover:text-primary transform hover:scale-105 transition-all">
                    Contact Sales
                </button>
            </div>
        </div>
    </section>

</x-app-layout>