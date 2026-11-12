<header x-data="{ mobileNavOpen: false }" class="sticky top-0 z-50 backdrop-blur-md transition-all" style="background-color: rgba(220, 245, 231, 0.95); border-bottom: 1px solid rgba(167, 243, 208, 0.5);">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="flex items-center justify-between py-4">
            
            <!-- Brand Logo -->
            <div class="flex items-center gap-2">
                <a href="{{ url('') }}" class="inline-flex items-center">
                    <img src="{{ theme_public_asset('images/whatsappbulklogo.png') }}" alt="Whatsappbulk" class="h-12 sm:h-14 md:h-16 w-auto max-w-[280px] object-contain transition-all" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3670/3670051.png';">
                </a>
            </div>

            <!-- Navigation Links (Centered, Home active capsule pill) -->
            <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                <a href="{{ url('') }}" class="px-5 py-2 rounded-full text-white font-semibold text-sm shadow-sm transition hover:opacity-95" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%); box-shadow: 0 4px 14px rgba(3, 111, 69, 0.35);">
                    {{ __("Home") }}
                </a>
                <a href="{{ url('') }}#modules" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-[#059669] transition">
                    {{ __("Features") }}
                </a>
                <a href="{{ url('') }}#pricing" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-[#059669] transition">
                    {{ __("Pricing") }}
                </a>
                <a href="{{ url('') }}#faqs" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-[#059669] transition">
                    {{ __("FAQs") }}
                </a>
                <a href="{{ url('contact') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-[#059669] transition">
                    {{ __("Contact") }}
                </a>
            </nav>

            <!-- Actions / Auth (Sign In + Sign Up ->) -->
            <div class="hidden md:flex items-center space-x-4">
                @if(Auth::check())
                    <a href="{{ url('app/dashboard') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-white font-bold text-sm shadow-sm transition hover:opacity-95" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%); box-shadow: 0 4px 14px rgba(3, 111, 69, 0.35);">
                        <span>{{ __('Dashboard') }}</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                @else
                    <a href="{{ url('auth/login') }}" class="text-sm font-bold text-slate-700 hover:text-[#059669] transition px-3 py-2">
                        {{ __("Sign In") }}
                    </a>
                    <a href="{{ url('auth/signup') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-white font-bold text-sm shadow-sm transition hover:opacity-95" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%); box-shadow: 0 4px 14px rgba(3, 111, 69, 0.35);">
                        <span>{{ __("Sign Up") }}</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                @endif
            </div>

            <!-- Mobile Hamburger Toggle -->
            <div class="md:hidden flex items-center">
                <button @click="mobileNavOpen = !mobileNavOpen" class="text-slate-700 p-2 focus:outline-none">
                    <i class="fas" :class="mobileNavOpen ? 'fa-times text-xl' : 'fa-bars text-xl'"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Nav Menu -->
        <div x-show="mobileNavOpen" x-transition class="md:hidden py-4 border-t border-slate-100 flex flex-col gap-3">
            <a href="{{ url('') }}" class="px-4 py-2 rounded-lg text-white font-semibold text-sm shadow-sm" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">{{ __("Home") }}</a>
            <a href="{{ url('') }}#modules" class="px-4 py-2 text-sm font-semibold text-slate-700">{{ __("Features") }}</a>
            <a href="{{ url('') }}#pricing" class="px-4 py-2 text-sm font-semibold text-slate-700">{{ __("Pricing") }}</a>
            <a href="{{ url('') }}#faqs" class="px-4 py-2 text-sm font-semibold text-slate-700">{{ __("FAQs") }}</a>
            <a href="{{ url('contact') }}" class="px-4 py-2 text-sm font-semibold text-slate-700">{{ __("Contact") }}</a>
            <div class="pt-3 border-t border-slate-100 flex flex-col gap-2">
                <a href="{{ url('auth/login') }}" class="text-center py-2 text-sm font-bold text-slate-700">{{ __("Sign In") }}</a>
                <a href="{{ url('auth/signup') }}" class="text-center py-2.5 rounded-full text-white font-bold text-sm shadow-sm" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">{{ __("Sign Up") }} →</a>
            </div>
        </div>
    </div>
</header>