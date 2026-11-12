<div class="hide-on-mobile relative flex flex-col justify-center flex-1 px-8 py-16 z-10 overflow-hidden" style="background: radial-gradient(circle at top left, rgba(110, 231, 183, 0.28), transparent 42%), linear-gradient(145deg, #052e16 0%, #064e3b 55%, #022c22 100%);">
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none" style="background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0));"></div>
    <div class="absolute top-20 right-10 w-72 h-72 rounded-full pointer-events-none" style="background: rgba(74, 222, 128, 0.12); filter: blur(10px);"></div>
    <div class="absolute bottom-10 left-10 w-56 h-56 rounded-full pointer-events-none" style="background: rgba(52, 211, 153, 0.16); filter: blur(8px);"></div>

    <div class="max-w-xl mx-auto relative w-full text-white">
        <div class="inline-flex items-center gap-2 px-4 py-2 mb-8 rounded-full border backdrop-blur-sm" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.18);">
            <span class="inline-flex items-center justify-center rounded-full w-8 h-8" style="background: rgba(37, 211, 102, 0.18);">
                <i class="fa-brands fa-whatsapp text-lg" style="color: #86efac;"></i>
            </span>
            <div>
                <div class="text-sm font-semibold tracking-px">Whatsappbulk</div>
                <div class="text-xs text-gray-200">WhatsApp Marketing Tool</div>
            </div>
        </div>

        <h2 class="mb-5 text-6xl md:text-7xl font-bold font-heading leading-tight">
            {{ $name ?? "Sign in to launch smarter WhatsApp campaigns" }}
        </h2>

        <p class="mb-8 text-lg leading-relaxed text-gray-200 max-w-lg">
            Bring team inbox, bulk campaigns, follow-up automation, and customer engagement into one focused WhatsApp workspace.
        </p>

        <ul class="space-y-3 mb-8 max-w-lg">
            <li class="flex items-start rounded-2xl border px-4 py-4" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1);">
                <span class="inline-flex items-center justify-center mt-1 mr-3 rounded-full w-8 h-8 flex-shrink-0" style="background: rgba(134, 239, 172, 0.16);">
                    <i class="fa-solid fa-check text-sm" style="color: #bbf7d0;"></i>
                </span>
                <span class="flex-1 text-base leading-relaxed text-gray-100">Launch segmented WhatsApp broadcasts with campaign-level control.</span>
            </li>
            <li class="flex items-start rounded-2xl border px-4 py-4" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1);">
                <span class="inline-flex items-center justify-center mt-1 mr-3 rounded-full w-8 h-8 flex-shrink-0" style="background: rgba(134, 239, 172, 0.16);">
                    <i class="fa-solid fa-check text-sm" style="color: #bbf7d0;"></i>
                </span>
                <span class="flex-1 text-base leading-relaxed text-gray-100">Automate replies, follow-ups, and nurture sequences for every lead.</span>
            </li>
            <li class="flex items-start rounded-2xl border px-4 py-4" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1);">
                <span class="inline-flex items-center justify-center mt-1 mr-3 rounded-full w-8 h-8 flex-shrink-0" style="background: rgba(134, 239, 172, 0.16);">
                    <i class="fa-solid fa-check text-sm" style="color: #bbf7d0;"></i>
                </span>
                <span class="flex-1 text-base leading-relaxed text-gray-100">Track conversation performance and team activity in real time.</span>
            </li>
        </ul>

        <div class="grid md:grid-cols-3 gap-4 mb-8">
            <div class="rounded-3xl px-5 py-5 border" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);">
                <div class="text-3xl font-bold mb-2" style="color: #dcfce7;">98%</div>
                <div class="text-sm text-gray-200 leading-normal">Message open rate</div>
            </div>
            <div class="rounded-3xl px-5 py-5 border" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);">
                <div class="text-3xl font-bold mb-2" style="color: #dcfce7;">24/7</div>
                <div class="text-sm text-gray-200 leading-normal">Automation uptime</div>
            </div>
            <div class="rounded-3xl px-5 py-5 border" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);">
                <div class="text-3xl font-bold mb-2" style="color: #dcfce7;">1 Team</div>
                <div class="text-sm text-gray-200 leading-normal">Unified inbox</div>
            </div>
        </div>

        <div class="rounded-4xl overflow-hidden border" style="background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.1); box-shadow: 0 30px 60px rgba(0,0,0,0.16);">
            <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color: rgba(255,255,255,0.1);">
                <div class="flex items-center gap-3">
                    <img class="h-9 w-auto" src="{{ url(get_option('website_logo_brand_dark', asset('public/img/logo-brand-dark.png'))) }}" alt="">
                    <div>
                        <div class="font-semibold text-white">Whatsappbulk Workspace</div>
                        <div class="text-xs text-gray-200">Centralize your WhatsApp operation</div>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="background: rgba(134, 239, 172, 0.16); color: #dcfce7;">
                    Built to scale
                </span>
            </div>
            <div class="p-6">
                <img class="w-full rounded-3xl border" style="border-color: rgba(255,255,255,0.1);" src="{{ theme_public_asset('images/headers/dashboard.png') }}" alt="Whatsappbulk dashboard preview">
            </div>
        </div>
    </div>
</div>
