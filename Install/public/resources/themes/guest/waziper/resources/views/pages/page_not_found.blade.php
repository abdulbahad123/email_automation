<section class="bg-[#f3fbf6] py-24 md:py-32">
    <div class="container mx-auto px-4">
        <div class="mx-auto max-w-3xl rounded-[2.5rem] border border-[#cfe9d8] bg-white px-8 py-14 text-center shadow-[0_28px_90px_rgba(44,122,68,0.08)] md:px-14 md:py-20">
            <span class="inline-flex items-center rounded-full border border-[#9ad9af] bg-[#effaf2] px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-[#1f7a45]">
                {{ __("404 / Missing page") }}
            </span>

            <div class="mx-auto mt-8 flex h-24 w-24 items-center justify-center rounded-[2rem] bg-[#dff6e7] text-4xl text-[#1f7a45]">
                <i class="fas fa-unlink"></i>
            </div>

            <h1 class="mt-8 text-4xl font-bold leading-tight text-[#112119] md:text-5xl">
                {{ __("This page is no longer here") }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-[#527060]">
                {{ __("The link may be outdated, the content may have moved, or the page never existed in this environment. Return to the homepage and continue from there.") }}
            </p>

            <a href="{{ url('/') }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-[#1f7a45] px-7 py-3.5 text-sm font-semibold text-white transition hover:bg-[#176338]">
                <i class="fas fa-arrow-left text-xs"></i>
                <span>{{ __("Back to homepage") }}</span>
            </a>
        </div>
    </div>
</section>
