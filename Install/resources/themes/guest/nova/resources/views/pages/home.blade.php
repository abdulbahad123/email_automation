<section class="relative overflow-hidden bg-[#f7f8ff]">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -left-24 top-0 h-80 w-80 rounded-full bg-indigo-100 blur-3xl opacity-70"></div>
        <div class="absolute right-0 top-24 h-96 w-96 rounded-full bg-violet-100 blur-3xl opacity-60"></div>
        <div class="absolute bottom-0 left-1/2 h-80 w-[32rem] -translate-x-1/2 rounded-full bg-blue-100 blur-3xl opacity-60"></div>
    </div>

    <div class="relative container px-4 mx-auto pt-28 pb-24 md:pt-32 md:pb-32">
        <div class="grid gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <div>
                <span class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-indigo-100">
                    <i class="fa-light fa-sparkles mr-2"></i>
                    {{ __("Built for real publishing operations") }}
                </span>

                <h1 class="mt-7 max-w-4xl text-6xl font-bold leading-none tracking-tight text-slate-950 md:text-8xl xl:text-9xl">
                    {{ __("Publish faster with AI, bulk posting, RSS scheduling, and team-ready tools.") }}
                </h1>

                <p class="mt-7 max-w-2xl text-lg font-medium leading-8 text-slate-600">
                    {{ __("Get one workspace for dashboard visibility, scheduled publishing, AI-assisted content, bulk posting, RSS automation, media search, captions, groups, files, proxies, and more.") }}
                </p>

                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="{{ url('auth/signup') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-7 py-4 text-base font-semibold text-white shadow-[0_18px_40px_rgba(79,70,229,0.22)] transition hover:bg-indigo-700">
                        {{ __("Start Free Trial") }}
                    </a>
                    <a href="{{ url('') }}#capabilities" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-7 py-4 text-base font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50">
                        {{ __("Explore Features") }}
                    </a>
                </div>

                <div class="mt-12 grid max-w-2xl gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
                        <div class="text-3xl font-bold text-slate-950">14+</div>
                        <div class="mt-2 text-sm font-medium text-slate-500">{{ __("Operational modules") }}</div>
                    </div>
                    <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
                        <div class="text-3xl font-bold text-slate-950">24/7</div>
                        <div class="mt-2 text-sm font-medium text-slate-500">{{ __("Automated schedules") }}</div>
                    </div>
                    <div class="rounded-3xl border border-white/70 bg-white/90 p-5 shadow-sm">
                        <div class="text-3xl font-bold text-slate-950">1</div>
                        <div class="mt-2 text-sm font-medium text-slate-500">{{ __("Unified workspace") }}</div>
                    </div>
                </div>
            </div>

            <div>
                <div class="relative mx-auto max-w-xl">
                    <div class="absolute -left-8 top-12 rounded-3xl border border-indigo-100 bg-white px-5 py-4 shadow-xl" style="z-index: 30;">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500">{{ __("AI Publishing") }}</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ __("Generate drafts, captions, and post variants faster") }}</p>
                    </div>

                    <div class="absolute -right-6 bottom-8 rounded-3xl border border-violet-100 bg-white px-5 py-4 shadow-xl" style="z-index: 30;">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-500">{{ __("RSS Schedules") }}</p>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ __("Keep channels active with automated feed publishing") }}</p>
                    </div>

                    <div class="relative overflow-hidden rounded-[2rem] border border-white/60 bg-white/95 p-4 shadow-[0_30px_80px_rgba(30,41,59,0.10)] backdrop-blur-sm" style="z-index: 10;">
                        <img class="w-full rounded-[1.5rem]" src="{{ theme_public_asset('images/headers/header.png') }}" alt="Platform dashboard preview">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="capabilities" class="bg-white py-24 md:py-32">
    <div class="container px-4 mx-auto">
        <div class="mx-auto mb-16 max-w-4xl text-center">
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-600">
                {{ __("Core capabilities") }}
            </span>
            <h2 class="mt-6 text-5xl font-bold leading-tight tracking-tight text-slate-950 md:text-7xl">
                {{ __("Everything needed to run daily publishing work without chaos") }}
            </h2>
            <p class="mt-5 text-lg font-medium leading-8 text-slate-500">
                {{ __("The platform is structured around the workflows your team actually uses every day: publishing, content creation, media handling, team coordination, and account operations.") }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-100 text-2xl text-indigo-600">
                    <i class="fa-light fa-paper-plane"></i>
                </div>
                <h3 class="mt-6 text-2xl font-bold text-slate-950">{{ __("Publishing engine") }}</h3>
                <p class="mt-4 text-base font-medium leading-8 text-slate-600">
                    {{ __("Run scheduled publishing, bulk posts, RSS schedules, and channel management from one clean dashboard built for repeatable execution.") }}
                </p>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-violet-100 text-2xl text-violet-600">
                    <i class="fa-light fa-wand-magic-sparkles"></i>
                </div>
                <h3 class="mt-6 text-2xl font-bold text-slate-950">{{ __("AI and content tools") }}</h3>
                <p class="mt-4 text-base font-medium leading-8 text-slate-600">
                    {{ __("Use AI Publishing, AI Contents, caption support, media search, and file handling to prepare content faster without leaving the workflow.") }}
                </p>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-slate-50 p-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-100 text-2xl text-blue-600">
                    <i class="fa-light fa-users"></i>
                </div>
                <h3 class="mt-6 text-2xl font-bold text-slate-950">{{ __("Team and account ops") }}</h3>
                <p class="mt-4 text-base font-medium leading-8 text-slate-600">
                    {{ __("Coordinate teams, groups, watermarking, proxies, and support without spreading work across separate admin tools.") }}
                </p>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#f8f9ff] py-24 md:py-32">
    <div class="container px-4 mx-auto">
        <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
            <div class="rounded-[2rem] border border-indigo-100 bg-white p-8 shadow-[0_24px_60px_rgba(15,23,42,0.06)]">
                <div class="grid gap-5">
                    <div class="rounded-[1.5rem] bg-slate-50 p-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-indigo-500">{{ __("Daily publishing") }}</p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-950">{{ __("Dashboard, schedules, campaigns, labels, and drafts") }}</h3>
                        <p class="mt-3 text-base font-medium leading-8 text-slate-600">
                            {{ __("Move from planning to posting with dashboard visibility, clear schedules, reusable labels, and draft management that keep execution organized.") }}
                        </p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-violet-500">{{ __("Content preparation") }}</p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-950">{{ __("AI contents, captions, files, and media discovery") }}</h3>
                        <p class="mt-3 text-base font-medium leading-8 text-slate-600">
                            {{ __("Prepare post assets faster with caption support, internal files, AI-generated content, and online media search inside the same workflow.") }}
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <span class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-indigo-600 ring-1 ring-indigo-100">
                    {{ __("Operational modules") }}
                </span>
                <h2 class="mt-6 text-5xl font-bold leading-tight tracking-tight text-slate-950 md:text-7xl">
                    {{ __("The product is stronger when the workflow matches the actual feature set") }}
                </h2>
                <p class="mt-6 text-lg font-medium leading-8 text-slate-500">
                    {{ __("The platform is not just a planner. It is a connected toolset for publishing operations, AI-assisted content work, group management, and account-level controls.") }}
                </p>

                <div class="mt-8 space-y-4">
                    <div class="flex items-start rounded-3xl border border-slate-200 bg-white px-5 py-5">
                        <i class="fa-solid fa-check mt-1 mr-4 text-indigo-600"></i>
                        <span class="font-medium text-slate-700">{{ __("Use Bulk Posts and RSS Schedules to keep content moving at scale without manual repetition.") }}</span>
                    </div>
                    <div class="flex items-start rounded-3xl border border-slate-200 bg-white px-5 py-5">
                        <i class="fa-solid fa-check mt-1 mr-4 text-indigo-600"></i>
                        <span class="font-medium text-slate-700">{{ __("Support AI Publishing, AI Contents, captions, and media search inside one production flow.") }}</span>
                    </div>
                    <div class="flex items-start rounded-3xl border border-slate-200 bg-white px-5 py-5">
                        <i class="fa-solid fa-check mt-1 mr-4 text-indigo-600"></i>
                        <span class="font-medium text-slate-700">{{ __("Manage teams, groups, watermarking, proxies, and support without fragmenting operations.") }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-white py-24 md:py-32">
    <div class="container px-4 mx-auto">
        <div class="rounded-[2rem] px-8 py-14 text-center md:px-14 md:py-16" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 52%, #4c1d95 100%); color: #ffffff;">
            <span class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold" style="background: rgba(255,255,255,0.12); color: #e0e7ff;">
                {{ __("Ready to upgrade your workflow?") }}
            </span>
            <h2 class="mx-auto mt-6 max-w-4xl text-5xl font-bold leading-tight tracking-tight md:text-7xl" style="color: #ffffff;">
                {{ __("Start with the modules you need and scale your publishing system from one workspace") }}
            </h2>
            <p class="mx-auto mt-6 max-w-3xl text-lg font-medium leading-8" style="color: rgba(224,231,255,0.92);">
                {{ __("Use the platform to centralize publishing, automate repetitive work, speed up content preparation, and keep team operations cleaner as you grow.") }}
            </p>

            <div class="mt-10 flex flex-wrap justify-center gap-4">
                <a href="{{ url('auth/signup') }}" class="inline-flex items-center justify-center rounded-2xl px-7 py-4 text-base font-semibold transition" style="background: #ffffff; color: #0f172a;">
                    {{ __("Create Your Account") }}
                </a>
                <a href="{{ url('pricing') }}" class="inline-flex items-center justify-center rounded-2xl px-7 py-4 text-base font-semibold transition" style="border: 1px solid rgba(255,255,255,0.18); background: rgba(255,255,255,0.10); color: #ffffff;">
                    {{ __("View Pricing") }}
                </a>
            </div>
        </div>
    </div>
</section>

@include("partials.pricing")
@include("partials.faqs")
@include("partials.home-blog")
