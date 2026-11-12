@php
    $dbBlogs = Home::getRecentBlogs(null, 3);
    $defaultBlogs = [
        [
            'slug' => 'how-to-run-high-converting-whatsapp-campaigns-in-2026',
            'title' => 'How to run high-converting WhatsApp campaigns in 2026',
            'desc' => 'Best practices for segmenting recipient lists, copywriting broadcast messages, and maintaining positive account health.',
            'created' => time() - (4 * 86400),
            'thumbnail' => ''
        ],
        [
            'slug' => 'auto-reply-and-chatbot-workflows-that-actually-save-time',
            'title' => 'Auto reply and chatbot workflows that actually save time',
            'desc' => 'How to setup instant response triggers and multi-step conversational funnels that qualify inbound customer leads 24/7.',
            'created' => time() - (10 * 86400),
            'thumbnail' => ''
        ],
        [
            'slug' => 'reporting-and-analytics-for-whatsapp-growth-teams',
            'title' => 'Reporting and analytics for WhatsApp growth teams',
            'desc' => 'Key metrics to track across open rates, response timelines, and group exports to quantify your marketing ROI.',
            'created' => time() - (14 * 86400),
            'thumbnail' => ''
        ]
    ];
    $blogsToRender = ($dbBlogs && $dbBlogs->isNotEmpty()) ? $dbBlogs : collect($defaultBlogs)->map(fn($b) => (object)$b);
@endphp

<section id="blog" class="py-24 bg-[#fafafa] border-t border-gray-100">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
            <div>
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-3" style="background: rgba(255, 85, 0, 0.08); color: #ff5500; border: 1px solid rgba(255, 85, 0, 0.2);">
                    BLOG
                </span>
                <h2 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight leading-tight">
                    Articles for teams building serious <span style="color: #ff5500;">WhatsApp operations</span>
                </h2>
                <p class="text-sm md:text-base text-gray-500 font-medium mt-2 max-w-2xl">
                    Playbooks, campaign ideas, support workflows, and practical guidance for turning WhatsApp into a real revenue channel.
                </p>
            </div>
            <a href="{{ url('blogs') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#ff5500] hover:text-[#d94800] transition whitespace-nowrap">
                <span>Browse all articles</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($blogsToRender as $blog)
                <article class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-xs hover:shadow-xl transition hover:-translate-y-1 flex flex-col justify-between">
                    <div>
                        <!-- Thumbnail Mockup matching Image 1 -->
                        <div class="h-44 w-full bg-gradient-to-br from-amber-50 via-orange-50 to-orange-100 p-4 flex items-center justify-center relative overflow-hidden">
                            <div class="w-36 h-28 bg-white/90 rounded-2xl shadow-sm border border-orange-200/50 p-3 flex flex-col justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded-full bg-[#ff5500] flex items-center justify-center text-white text-[8px]"><i class="fab fa-whatsapp"></i></div>
                                    <span class="text-[9px] font-bold text-gray-800">Whatsappbulk</span>
                                </div>
                                <div class="space-y-1">
                                    <div class="h-1.5 bg-orange-200 rounded-full w-full"></div>
                                    <div class="h-1.5 bg-orange-100 rounded-full w-4/5"></div>
                                </div>
                                <div class="flex justify-between items-center text-[8px] text-gray-400 font-semibold">
                                    <span>Campaign</span>
                                    <span class="text-green-600 font-bold">98% Sent</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <span class="text-[11px] font-bold text-gray-400 block mb-2">
                                {{ !empty($blog->created) ? (is_numeric($blog->created) ? date('M d, Y', $blog->created) : $blog->created) : 'Oct 12, 2026' }}
                            </span>
                            <h3 class="text-base font-bold text-gray-900 leading-snug hover:text-[#ff5500] transition line-clamp-2">
                                <a href="{{ url('blogs/' . ($blog->slug ?? '')) }}">{{ $blog->title }}</a>
                            </h3>
                            <p class="text-xs text-gray-500 mt-2 line-clamp-2 leading-relaxed">
                                {{ Str::limit(strip_tags($blog->desc ?? ''), 95) }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 pb-6 pt-0">
                        <a href="{{ url('blogs/' . ($blog->slug ?? '')) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#ff5500] hover:text-[#d94800] transition">
                            <span>Read more</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>