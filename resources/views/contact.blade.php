<x-app-layout>
    {{-- Hero Section --}}
    <section class="relative bg-gradient-to-br from-primary to-secondary py-20 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-1/4 w-72 h-72 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-blue-300 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl lg:text-6xl font-black text-white mb-6">
                Get In <span class="text-blue-300">Touch</span>
            </h1>
            <p class="text-xl text-white/90 max-w-3xl mx-auto">
                Have questions? We're here to help. Reach out to our team and we'll respond as soon as possible.
            </p>
        </div>
    </section>

    {{-- Contact Form & Info Section --}}
    <section class="py-20 px-6 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12">
                {{-- Contact Form --}}
                <div class="bg-white p-8 rounded-2xl shadow-xl">
                    <h2 class="text-3xl font-black text-gray-900 mb-6">Send Us a Message</h2>

                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-green-700 font-semibold">{{ session('success') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-700 font-semibold">{{ session('error') }}</p>
                        </div>
                    @endif

                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6">
                        @csrf

                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Full Name *</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address *</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('email') border-red-500 @enderror">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label for="phone" class="block text-sm font-bold text-gray-700 mb-2">Phone Number (Optional)</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="{{ old('phone') }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('phone') border-red-500 @enderror">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Subject --}}
                        <div>
                            <label for="subject" class="block text-sm font-bold text-gray-700 mb-2">Subject *</label>
                            <input 
                                type="text" 
                                id="subject" 
                                name="subject" 
                                value="{{ old('subject') }}"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all @error('subject') border-red-500 @enderror">
                            @error('subject')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Message --}}
                        <div>
                            <label for="message" class="block text-sm font-bold text-gray-700 mb-2">Message *</label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="6" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <button 
                            type="submit"
                            class="w-full bg-primary hover:bg-secondary text-white font-bold py-4 rounded-lg transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                            Send Message
                        </button>
                    </form>
                </div>

                {{-- Contact Information --}}
                <div class="space-y-8">
                    {{-- Contact Info Card --}}
                    <div class="bg-white p-8 rounded-2xl shadow-xl">
                        <h2 class="text-3xl font-black text-gray-900 mb-6">Contact Information</h2>
                        
                        <div class="space-y-6">
                            {{-- Email --}}
                            <div class="flex items-start space-x-4">
                                <div class="bg-primary w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-envelope text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 mb-1">Email Us</h3>
                                    <a href="mailto:support@hifastlink.com" class="text-primary hover:underline">support@hifastlink.com</a>
                                </div>
                            </div>

                            {{-- Phone --}}
                            <div class="flex items-start space-x-4">
                                <div class="bg-green-600 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-phone text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 mb-1">Call Us</h3>
                                    <a href="tel:+2348012345678" class="text-primary hover:underline">+234 801 234 5678</a>
                                </div>
                            </div>

                            {{-- WhatsApp --}}
                            <div class="flex items-start space-x-4">
                                <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fa-brands fa-whatsapp text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 mb-1">WhatsApp</h3>
                                    <a href="https://wa.me/2348012345678" target="_blank" class="text-primary hover:underline">+234 801 234 5678</a>
                                </div>
                            </div>

                            {{-- Location --}}
                            <div class="flex items-start space-x-4">
                                <div class="bg-red-600 w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-map-marker-alt text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 mb-1">Visit Us</h3>
                                    <p class="text-gray-600">Lagos, Nigeria</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Business Hours --}}
                    <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-2xl shadow-xl text-white">
                        <h2 class="text-2xl font-black mb-6">Business Hours</h2>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="font-semibold">Monday - Friday:</span>
                                <span>8:00 AM - 6:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Saturday:</span>
                                <span>9:00 AM - 4:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-semibold">Sunday:</span>
                                <span>Closed</span>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-white/20">
                            <p class="text-sm text-white/90">
                                <i class="fa-solid fa-circle-info mr-2"></i>
                                24/7 emergency support available for enterprise clients
                            </p>
                        </div>
                    </div>

                    {{-- Social Media --}}
                    <div class="bg-white p-8 rounded-2xl shadow-xl">
                        <h2 class="text-2xl font-black text-gray-900 mb-6">Follow Us</h2>
                        
                        <div class="flex space-x-4">
                            <a href="#" class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-700 transition-colors">
                                <i class="fa-brands fa-facebook-f text-white text-xl"></i>
                            </a>
                            <a href="#" class="w-12 h-12 bg-blue-400 rounded-lg flex items-center justify-center hover:bg-blue-500 transition-colors">
                                <i class="fa-brands fa-twitter text-white text-xl"></i>
                            </a>
                            <a href="#" class="w-12 h-12 bg-pink-600 rounded-lg flex items-center justify-center hover:bg-pink-700 transition-colors">
                                <i class="fa-brands fa-instagram text-white text-xl"></i>
                            </a>
                            <a href="#" class="w-12 h-12 bg-blue-700 rounded-lg flex items-center justify-center hover:bg-blue-800 transition-colors">
                                <i class="fa-brands fa-linkedin-in text-white text-xl"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
