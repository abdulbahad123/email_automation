@php
    $isContactPage = request()->is('contact*');
    $isHomePage = (request()->is('/') || request()->is('')) && !$isContactPage;
@endphp

<header x-data="{ mobileNavOpen: false }" class="sticky top-0 z-50 transition-all" style="background-color: #dcf5e7; border-bottom: 1px solid rgba(167, 243, 208, 0.7);">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="flex items-center justify-between py-4">
            
            <!-- Brand Logo -->
            <div class="flex items-center gap-2">
                <a href="{{ url('') }}" class="inline-flex items-center">
                    <img src="{{ theme_public_asset('images/whatsappbulklogo.png') }}" alt="Whatsappbulk" class="h-12 sm:h-14 md:h-16 w-auto max-w-[280px] object-contain transition-all" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3670/3670051.png';">
                </a>
            </div>

            <!-- Navigation Links (Centered, Dynamic Active Pill) -->
            <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
                <a href="{{ url('') }}" 
                   class="{{ $isHomePage ? 'px-5 py-2 rounded-full text-white font-semibold text-sm shadow-sm transition hover:opacity-95' : 'px-4 py-2 text-sm font-semibold text-slate-600 hover:text-[#059669] transition' }}" 
                   {!! $isHomePage ? 'style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%); box-shadow: 0 4px 14px rgba(3, 111, 69, 0.35);"' : '' !!}>
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
                <a href="{{ url('contact') }}" 
                   class="{{ $isContactPage ? 'px-5 py-2 rounded-full text-white font-semibold text-sm shadow-sm transition hover:opacity-95' : 'px-4 py-2 text-sm font-semibold text-slate-600 hover:text-[#059669] transition' }}" 
                   {!! $isContactPage ? 'style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%); box-shadow: 0 4px 14px rgba(3, 111, 69, 0.35);"' : '' !!}>
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
                <button type="button" @click="mobileNavOpen = true" class="text-slate-800 p-2.5 rounded-xl bg-white/70 border border-emerald-200/80 shadow-xs focus:outline-none transition active:scale-95" aria-label="Open Mobile Menu">
                    <i class="fas fa-bars text-xl text-[#059669]"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer & Backdrop Teleported to Body to Escape Any Header Stacking Context -->
    <template x-teleport="body">
        <div>
            <!-- Mobile Drawer Backdrop Overlay -->
            <div x-show="mobileNavOpen" 
                 x-transition:enter="transition-opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileNavOpen = false" 
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[99998] md:hidden"
                 style="display: none;">
            </div>

            <!-- Mobile Offcanvas Drawer (Smooth Slide Right to Left, Full Viewport Height) -->
            <div x-show="mobileNavOpen"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-250 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="fixed top-0 right-0 bottom-0 w-[300px] max-w-[85vw] h-screen h-[100dvh] bg-[#f8fdfa] shadow-2xl z-[99999] flex flex-col justify-between p-6 md:hidden overflow-y-auto border-l border-emerald-100"
                 style="display: none;">
                
                <div>
                    <!-- Drawer Header: Brand Logo & Close 'X' Button -->
                    <div class="flex items-center justify-between pb-5 border-b border-emerald-100 mb-6">
                        <a href="{{ url('') }}" @click="mobileNavOpen = false" class="inline-flex items-center">
                            <img src="{{ theme_public_asset('images/whatsappbulklogo.png') }}" alt="Whatsappbulk" class="h-10 w-auto object-contain" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3670/3670051.png';">
                        </a>
                        <button type="button" @click="mobileNavOpen = false" class="w-9 h-9 rounded-full bg-white border border-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-100 transition shadow-xs focus:outline-none" aria-label="Close Mobile Menu">
                            <i class="fas fa-times text-base"></i>
                        </button>
                    </div>

                    <!-- Drawer Navigation Links -->
                    <nav class="flex flex-col gap-2">
                        <a href="{{ url('') }}" 
                           @click="mobileNavOpen = false"
                           class="px-4 py-3 rounded-xl font-bold text-sm transition flex items-center justify-between {{ $isHomePage ? 'text-white shadow-md' : 'text-slate-700 hover:bg-white hover:text-[#059669]' }}"
                           {!! $isHomePage ? 'style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);"' : '' !!}>
                            <span><i class="fas fa-home me-2.5 opacity-80"></i> {{ __("Home") }}</span>
                            @if($isHomePage)
                                <i class="fas fa-check text-xs"></i>
                            @endif
                        </a>
                        <a href="{{ url('') }}#modules" 
                           @click="mobileNavOpen = false"
                           class="px-4 py-3 rounded-xl text-sm font-bold text-slate-700 hover:bg-white hover:text-[#059669] transition flex items-center">
                            <i class="fas fa-cubes me-2.5 opacity-80 text-emerald-600"></i> {{ __("Features") }}
                        </a>
                        <a href="{{ url('') }}#pricing" 
                           @click="mobileNavOpen = false"
                           class="px-4 py-3 rounded-xl text-sm font-bold text-slate-700 hover:bg-white hover:text-[#059669] transition flex items-center">
                            <i class="fas fa-tags me-2.5 opacity-80 text-emerald-600"></i> {{ __("Pricing") }}
                        </a>
                        <a href="{{ url('') }}#faqs" 
                           @click="mobileNavOpen = false"
                           class="px-4 py-3 rounded-xl text-sm font-bold text-slate-700 hover:bg-white hover:text-[#059669] transition flex items-center">
                            <i class="fas fa-circle-question me-2.5 opacity-80 text-emerald-600"></i> {{ __("FAQs") }}
                        </a>
                        <a href="{{ url('contact') }}" 
                           @click="mobileNavOpen = false"
                           class="px-4 py-3 rounded-xl font-bold text-sm transition flex items-center justify-between {{ $isContactPage ? 'text-white shadow-md' : 'text-slate-700 hover:bg-white hover:text-[#059669]' }}"
                           {!! $isContactPage ? 'style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);"' : '' !!}>
                            <span><i class="fas fa-envelope me-2.5 opacity-80"></i> {{ __("Contact") }}</span>
                            @if($isContactPage)
                                <i class="fas fa-check text-xs"></i>
                            @endif
                        </a>
                    </nav>
                </div>

                <!-- Drawer Auth & Actions -->
                <div class="pt-6 border-t border-emerald-100 flex flex-col gap-2.5">
                    @if(Auth::check())
                        <a href="{{ url('app/dashboard') }}" class="w-full text-center py-3 rounded-xl text-white font-bold text-sm shadow-md transition hover:opacity-95" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">
                            <i class="fas fa-gauge-high me-1.5"></i> {{ __('Dashboard') }}
                        </a>
                    @else
                        <a href="{{ url('auth/login') }}" class="w-full text-center py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-800 hover:bg-slate-50 transition shadow-2xs">
                            {{ __("Sign In") }}
                        </a>
                        <a href="{{ url('auth/signup') }}" class="w-full text-center py-3 rounded-xl text-white font-bold text-sm shadow-md transition hover:opacity-95" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">
                            <span>{{ __("Sign Up") }}</span>
                            <i class="fas fa-arrow-right text-xs ms-1"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </template>
</header>