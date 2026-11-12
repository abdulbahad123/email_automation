@php
    $dbFaqs = Home::getFaqs();
    $defaultFaqs = [
        [
            'id' => 1,
            'title' => 'What is Whatsappbulk?',
            'content' => 'Whatsappbulk is an all-in-one WhatsApp automation and marketing workspace. It provides high-speed bulk messaging, AI smart replies, automated chatbots, contact segmentation, and detailed delivery reports all from one unified dashboard.'
        ],
        [
            'id' => 2,
            'title' => 'Is it safe to use for WhatsApp Marketing?',
            'content' => 'Yes. Whatsappbulk utilizes anti-ban rate limiting, customizable randomized delays between dispatches, and campaign pacing algorithms to ensure maximum delivery while protecting your WhatsApp account numbers.'
        ],
        [
            'id' => 3,
            'title' => 'Can I send bulk messages to multiple contacts?',
            'content' => 'Yes. You can import contacts via CSV or Excel, extract participants directly from existing WhatsApp groups, build segmented target groups, and send personalized message sequences at scale.'
        ],
        [
            'id' => 4,
            'title' => 'Do you offer a free plan?',
            'content' => 'Yes! Our Whatsappbulk Starter plan is completely free and includes up to 10,000 monthly messages, AI content features, and access to the complete campaign management toolkit.'
        ]
    ];
    $faqsToRender = ($dbFaqs && $dbFaqs->isNotEmpty()) ? $dbFaqs : collect($defaultFaqs)->map(fn($f) => (object)$f);
@endphp

<section id="faqs" class="py-24 bg-white border-t border-gray-100">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start" x-data="{ openFaq: 1 }">
            
            <!-- Left Column: Heading & Accordion -->
            <div class="lg:col-span-8">
                <div class="mb-8">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-3" style="background: rgba(255, 85, 0, 0.08); color: #ff5500; border: 1px solid rgba(255, 85, 0, 0.2);">
                        {!! get_option('landing_faq_badge', 'CLEAR ANSWERS FOR TEAMS RUNNING WHATSAPP AT SCALE') !!}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight leading-tight mb-3">
                        {!! get_option('landing_faq_title', 'Clear <span style="color: #ff5500;\">answers</span> for teams running WhatsApp at scale') !!}
                    </h2>
                    <p class="text-sm md:text-base text-gray-500 font-medium leading-relaxed max-w-2xl">
                        {{ get_option('landing_faq_subtitle', 'Everything here is written for real operators: lead capture, inbox routing, campaign sending, and account safety.') }}
                    </p>
                </div>

                <!-- Accordion Items -->
                <div class="space-y-4">
                    @foreach($faqsToRender as $faq)
                        <div class="border border-gray-200 rounded-2xl p-5 transition bg-[#fafafa] hover:bg-white hover:border-[#ff5500]/30 shadow-2xs">
                            <button
                                type="button"
                                class="w-full flex items-center justify-between text-left font-bold text-gray-900 text-base"
                                x-on:click="openFaq = (openFaq === {{ $faq->id }} ? null : {{ $faq->id }})"
                            >
                                <span>{{ $faq->title }}</span>
                                <span class="w-7 h-7 rounded-full bg-white border border-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 transition"
                                      :class="openFaq === {{ $faq->id }} ? 'bg-[#ff5500] text-white border-[#ff5500]' : ''">
                                    <i class="fas" :class="openFaq === {{ $faq->id }} ? 'fa-minus' : 'fa-plus'"></i>
                                </span>
                            </button>
                            <div x-show="openFaq === {{ $faq->id }}" x-collapse class="mt-4 pt-4 border-t border-gray-200 text-sm text-gray-600 leading-relaxed">
                                {!! $faq->content !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Column: Need More Help Box -->
            <div class="lg:col-span-4">
                <div class="bg-[#fafafa] rounded-3xl border border-gray-200 p-8 text-center flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-[#fff0e6] flex items-center justify-center text-[#ff5500] text-2xl mb-4">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Need more help?</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">
                        If your flow is more complex than a quick FAQ can solve, contact us and we will help you map the right setup.
                    </p>
                    <a href="{{ url('contact') }}" class="w-full py-3 rounded-full text-white font-bold text-sm shadow-md transition hover:opacity-95" style="background: #ff5500; box-shadow: 0 4px 14px rgba(255, 85, 0, 0.3);">
                        Contact Sales <i class="fas fa-arrow-right text-xs ml-1"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>