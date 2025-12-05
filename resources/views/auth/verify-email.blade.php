<x-guest-layout>
    <!-- Verification Icon -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full mb-6">
            <i class="fa-solid fa-envelope-circle-check text-5xl text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600"></i>
        </div>
        <h2 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 mb-3">
            Verify Your Email
        </h2>
        <p class="text-gray-500">We've sent you a verification link</p>
    </div>

    <!-- Information Message -->
    <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-6 mb-6">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-circle-info text-2xl text-blue-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-gray-700 leading-relaxed">
                    {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session('status') == 'verification-link-sent')
        <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-6 mb-6 animate-fade-in">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-circle-check text-2xl text-green-600"></i>
                </div>
                <div class="flex-1">
                    <p class="text-green-800 font-medium leading-relaxed">
                        {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="space-y-4">
        <!-- Resend Button -->
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button 
                type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-blue-300 group"
            >
                <span class="flex items-center justify-center">
                    <i class="fa-solid fa-paper-plane mr-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                    {{ __('Resend Verification Email') }}
                </span>
            </button>
        </form>

        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button 
                type="submit"
                class="w-full bg-white border-2 border-gray-300 hover:border-red-400 text-gray-700 hover:text-red-600 font-semibold py-4 rounded-2xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-lg group"
            >
                <span class="flex items-center justify-center">
                    <i class="fa-solid fa-right-from-bracket mr-2 group-hover:scale-110 inline-block transition-transform duration-300"></i>
                    {{ __('Log Out') }}
                </span>
            </button>
        </form>
    </div>

    <!-- Help Text -->
    <div class="text-center mt-8 pt-6 border-t-2 border-gray-200">
        <p class="text-sm text-gray-500 mb-2">
            <i class="fa-solid fa-lightbulb text-yellow-500 mr-1"></i>
            <strong>Tip:</strong> Check your spam folder if you don't see the email
        </p>
        <p class="text-xs text-gray-400">
            Need help? Contact our support team at 
            <a href="mailto:support@hifastlink.com" class="text-blue-600 hover:underline font-medium">
                support@hifastlink.com
            </a>
        </p>
    </div>
</x-guest-layout>