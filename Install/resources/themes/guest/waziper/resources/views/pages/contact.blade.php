@php
    $website = get_option('contact_company_website', 'https://yourcompany.com');
    $email = get_option('contact_email', 'support@yourcompany.com');
    $phone = get_option('contact_phone_number', '+1 234 567 890');
    $workingHours = get_option('contact_working_hours', 'Mon - Fri: 09:00 AM - 06:00 PM');
    $location = get_option('contact_location', '123 Main Street, City, Country');
    $mapIframe = get_option('contact_map_iframe', '');
    $pageTitle = get_option('contact_page_title', __('Talk to the team behind your WhatsApp workflow'));
    $pageDesc = get_option('contact_page_desc', __('Reach out for onboarding help, pricing questions, infrastructure guidance, or support with a production WhatsApp setup.'));
    
    $recaptchaStatus = get_option('contact_recaptcha_status', get_option('auth_google_recaptcha_status', 1));
    $recaptchaSiteKey = get_option('contact_recaptcha_site_key', get_option('auth_google_recaptcha_site_key', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'));
    if (empty($recaptchaSiteKey)) {
        $recaptchaSiteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
    }
@endphp

<section class="relative py-16 md:py-24 overflow-hidden" style="background-color: #eafbf3; background: linear-gradient(180deg, #d8f5e5 0%, #ebfcf3 40%, #eafbf3 100%);">
    
    <!-- Ambient Green Background Glows -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 -left-20 w-80 h-80 bg-emerald-300/30 rounded-full blur-3xl pointer-events-none"></div>

    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        
        <!-- Header Text -->
        <div class="mx-auto max-w-3xl text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-[#065f46] border border-emerald-300/70 shadow-xs mb-4">
                <i class="fas fa-headset text-[11px] text-[#059669]"></i>
                <span>{{ __("Contact us") }}</span>
            </span>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                {{ $pageTitle }}
            </h1>
            <p class="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                {{ $pageDesc }}
            </p>
        </div>

        <!-- Success & Error Alert Banners -->
        @if(session('success'))
            <div class="max-w-4xl mx-auto mb-8 bg-emerald-50 border border-emerald-300 text-[#065f46] px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <i class="fas fa-circle-check text-xl text-[#059669] shrink-0"></i>
                <div class="text-sm font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-4xl mx-auto mb-8 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm">
                <i class="fas fa-circle-exclamation text-xl text-red-500 shrink-0"></i>
                <div class="text-sm font-semibold">{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-4xl mx-auto mb-8 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl shadow-sm">
                <div class="font-bold text-sm mb-1 flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation"></i> {{ __('Please correct the errors below:') }}
                </div>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Main Content Grid: Left Location Containers + Right Enquiry Form -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
            
            <!-- ========================================================= -->
            <!-- LEFT COLUMN: LOCATION & CONTACT DETAILS (Each Container)  -->
            <!-- ========================================================= -->
            <div class="lg:col-span-5 flex flex-col gap-4 order-2 lg:order-1">
                
                <div class="mb-2">
                    <h2 class="text-xl font-black text-slate-900">{{ __("Our Information") }}</h2>
                    <p class="text-xs text-slate-500">{{ __("Connect with our support and sales teams directly.") }}</p>
                </div>

                <!-- Container 1: Website -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-200/70 shadow-[0_4px_20px_rgba(5,150,105,0.05)] transition hover:shadow-md hover:border-emerald-300">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">{{ __("Website") }}</span>
                            <a href="{{ $website }}" target="_blank" rel="noopener" class="text-base font-bold text-slate-900 hover:text-[#059669] transition break-all block">
                                {{ $website }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Container 2: Email -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-200/70 shadow-[0_4px_20px_rgba(5,150,105,0.05)] transition hover:shadow-md hover:border-emerald-300">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">{{ __("Email") }}</span>
                            <a href="mailto:{{ $email }}" class="text-base font-bold text-slate-900 hover:text-[#059669] transition break-all block">
                                {{ $email }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Container 3: Phone / WhatsApp -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-200/70 shadow-[0_4px_20px_rgba(5,150,105,0.05)] transition hover:shadow-md hover:border-emerald-300">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">{{ __("Phone / WhatsApp") }}</span>
                            <a href="tel:{{ $phone }}" class="text-base font-bold text-slate-900 hover:text-[#059669] transition block">
                                {{ $phone }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Container 4: Working Hours -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-200/70 shadow-[0_4px_20px_rgba(5,150,105,0.05)] transition hover:shadow-md hover:border-emerald-300">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">{{ __("Working Hours") }}</span>
                            <span class="text-base font-bold text-slate-900 block">
                                {{ $workingHours }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Container 5: Location / Address -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-200/70 shadow-[0_4px_20px_rgba(5,150,105,0.05)] transition hover:shadow-md hover:border-emerald-300">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] flex items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">{{ __("Address / Location") }}</span>
                            <span class="text-base font-bold text-slate-900 block leading-snug">
                                {{ $location }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Container 6: Live Map Embed (if configured in Admin) -->
                @if(!empty($mapIframe))
                    <div class="bg-white rounded-2xl p-3 border border-emerald-200/70 shadow-[0_4px_20px_rgba(5,150,105,0.05)] overflow-hidden">
                        @if(str_contains($mapIframe, '<iframe'))
                            <div class="w-full h-48 rounded-xl overflow-hidden [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0">
                                {!! $mapIframe !!}
                            </div>
                        @else
                            <iframe src="{{ $mapIframe }}" class="w-full h-48 rounded-xl border-0" loading="lazy"></iframe>
                        @endif
                    </div>
                @endif

            </div>

            <!-- ========================================================= -->
            <!-- RIGHT COLUMN: ENQUIRY FORM WITH RECAPTCHA                 -->
            <!-- ========================================================= -->
            <div class="lg:col-span-7 flex justify-center lg:justify-start order-1 lg:order-2">
                <div class="bg-white rounded-[2rem] border border-emerald-200/80 p-6 sm:p-8 md:p-9 shadow-[0_20px_60px_rgba(5,150,105,0.08)] w-full max-w-xl mx-auto lg:mx-0">
                    
                    <div class="mb-6">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-[#065f46] border border-emerald-200 mb-2">
                            <i class="fas fa-paper-plane text-[10px] text-[#059669]"></i>
                            {{ __("Send Enquiry") }}
                        </span>
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                            {{ __("Send us a message") }}
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ __("Fill in your details below and our team will get back to you within 24 hours.") }}
                        </p>
                    </div>

                    <form method="POST" action="{{ url('contact') }}" class="space-y-4">
                        @csrf

                        <!-- Row 1: Name & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    {{ __("Full Name") }} <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                    <input type="text" id="contact_name" name="name" value="{{ old('name') }}" required placeholder="{{ __('e.g. John Doe') }}" class="w-full pl-10 pr-4 py-3 bg-slate-50/70 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#059669] focus:ring-2 focus:ring-[#059669]/20 focus:outline-none transition">
                                </div>
                            </div>

                            <div>
                                <label for="contact_email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    {{ __("Email Address") }} <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fas fa-envelope text-xs"></i>
                                    </div>
                                    <input type="email" id="contact_email" name="email" value="{{ old('email') }}" required placeholder="{{ __('e.g. john@company.com') }}" class="w-full pl-10 pr-4 py-3 bg-slate-50/70 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#059669] focus:ring-2 focus:ring-[#059669]/20 focus:outline-none transition">
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Phone & Subject -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact_phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    {{ __("Phone / WhatsApp") }}
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fab fa-whatsapp text-xs"></i>
                                    </div>
                                    <input type="text" id="contact_phone" name="phone" value="{{ old('phone') }}" placeholder="{{ __('+1 234 567 890') }}" class="w-full pl-10 pr-4 py-3 bg-slate-50/70 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#059669] focus:ring-2 focus:ring-[#059669]/20 focus:outline-none transition">
                                </div>
                            </div>

                            <div>
                                <label for="contact_subject" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    {{ __("Subject / Enquiry Type") }}
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fas fa-tag text-xs"></i>
                                    </div>
                                    <select id="contact_subject" name="subject" class="w-full pl-10 pr-4 py-3 bg-slate-50/70 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:border-[#059669] focus:ring-2 focus:ring-[#059669]/20 focus:outline-none transition">
                                        <option value="General Enquiry" {{ old('subject') == 'General Enquiry' ? 'selected' : '' }}>{{ __("General Enquiry") }}</option>
                                        <option value="Pricing & Plans" {{ old('subject') == 'Pricing & Plans' ? 'selected' : '' }}>{{ __("Pricing & Plans") }}</option>
                                        <option value="Enterprise WhatsApp Setup" {{ old('subject') == 'Enterprise WhatsApp Setup' ? 'selected' : '' }}>{{ __("Enterprise WhatsApp Setup") }}</option>
                                        <option value="Technical Support" {{ old('subject') == 'Technical Support' ? 'selected' : '' }}>{{ __("Technical Support") }}</option>
                                        <option value="Custom Integration" {{ old('subject') == 'Custom Integration' ? 'selected' : '' }}>{{ __("Custom Integration") }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Message -->
                        <div>
                            <label for="contact_message" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                {{ __("Your Message") }} <span class="text-red-500">*</span>
                            </label>
                            <textarea id="contact_message" name="message" rows="4" required placeholder="{{ __('Tell us how we can help with your WhatsApp automation or campaigns...') }}" class="w-full p-4 bg-slate-50/70 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-[#059669] focus:ring-2 focus:ring-[#059669]/20 focus:outline-none transition resize-none">{{ old('message') }}</textarea>
                        </div>

                        <!-- Google reCAPTCHA v2 Widget -->
                        <div class="pt-2 pb-2">
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                        </div>

                        <!-- Submit Button with Green Gradient -->
                        <div class="pt-2">
                            <button type="submit" class="w-full py-4 rounded-full text-white font-bold text-sm tracking-wide shadow-lg shadow-emerald-700/25 transition transform hover:-translate-y-0.5 hover:opacity-95 flex items-center justify-center gap-2" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">
                                <span>{{ __("Submit Enquiry") }}</span>
                                <i class="fas fa-arrow-right text-xs"></i>
                            </button>
                        </div>

                        <p class="text-center text-xs text-slate-400 pt-2 flex items-center justify-center gap-1.5">
                            <i class="fas fa-shield-alt text-[11px] text-[#059669]"></i>
                            <span>{{ __("We respect your privacy. No spam guaranteed.") }}</span>
                        </p>

                    </form>

                </div>
            </div>

        </div>

    </div>
</section>
