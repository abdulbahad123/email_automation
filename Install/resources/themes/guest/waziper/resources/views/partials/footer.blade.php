<footer class="pt-16 pb-12 bg-white border-t border-slate-100">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="flex flex-col lg:flex-row items-center justify-between pb-10 border-b border-slate-100 gap-8">
            
            <!-- Brand Column -->
            <div class="flex flex-col items-center lg:items-start text-center lg:text-left">
                <a href="{{ url('') }}" class="inline-flex items-center mb-2">
                    <img src="{{ theme_public_asset('images/whatsappbulklogo.png') }}" alt="Whatsappbulk" class="h-12 sm:h-14 md:h-15 w-auto max-w-[270px] object-contain transition-all" onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3670/3670051.png';">
                </a>
                <span class="text-xs font-semibold text-slate-400">{{ get_option('landing_footer_tagline', 'WhatsApp Marketing & Automation Tool') }}</span>
            </div>

            <!-- Footer Links -->
            <ul class="flex flex-wrap items-center justify-center gap-6 md:gap-8 text-sm font-semibold text-slate-600">
                <li><a class="hover:text-[#059669] transition" href="{{ url('') }}">{{ __("Home") }}</a></li>
                <li><a class="hover:text-[#059669] transition" href="{{ url('') }}#modules">{{ __("Features") }}</a></li>
                <li><a class="hover:text-[#059669] transition" href="{{ url('') }}#pricing">{{ __("Pricing") }}</a></li>
                <li><a class="hover:text-[#059669] transition" href="{{ url('') }}#faqs">{{ __("FAQs") }}</a></li>
                <li><a class="hover:text-[#059669] transition" href="{{ url('contact') }}">{{ __("Contact") }}</a></li>
            </ul>

            <!-- Social Links -->
            <div class="flex items-center space-x-3">
                <a class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-50 border border-slate-200 text-slate-600 hover:text-[#059669] hover:border-[#059669] transition" href="{{ get_option('social_page_facebook', 'https://facebook.com') }}" target="_blank" rel="noopener"><i class="fab fa-facebook-f text-xs"></i></a>
                <a class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-50 border border-slate-200 text-slate-600 hover:text-[#059669] hover:border-[#059669] transition" href="{{ get_option('social_page_x', 'https://twitter.com') }}" target="_blank" rel="noopener"><i class="fab fa-x-twitter text-xs"></i></a>
                <a class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-50 border border-slate-200 text-slate-600 hover:text-[#059669] hover:border-[#059669] transition" href="https://linkedin.com" target="_blank" rel="noopener"><i class="fab fa-linkedin-in text-xs"></i></a>
                <a class="w-9 h-9 flex items-center justify-center rounded-full bg-slate-50 border border-slate-200 text-slate-600 hover:text-[#059669] hover:border-[#059669] transition" href="https://youtube.com" target="_blank" rel="noopener"><i class="fab fa-youtube text-xs"></i></a>
            </div>
        </div>

        <!-- Copyright & Legal -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-4">
            <p>© {{ date('Y') }} Whatsappbulk. {{ __("All Rights Reserved.") }}</p>
            <div class="flex items-center space-x-6">
                <a class="hover:text-[#059669] transition" href="{{ url('privacy-policy') }}">{{ __("Privacy Policy") }}</a>
                <span class="text-slate-200">·</span>
                <a class="hover:text-[#059669] transition" href="{{ url('terms-of-service') }}">{{ __("Terms & Conditions") }}</a>
            </div>
        </div>
    </div>
</footer>