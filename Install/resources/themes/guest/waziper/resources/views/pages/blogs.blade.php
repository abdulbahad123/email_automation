@php
    $countPostBlog = Home::countPostBlog();
    $blogs = Home::getBlogs();
    $categories = Home::getBlogCategories();
    $tags = Home::getBlogTags();
@endphp

<section class="bg-[#f3fbf6] py-20 md:py-28">
    <div class="container mx-auto px-4">
        <div class="mx-auto max-w-4xl text-center">
            <span class="inline-flex items-center rounded-full border border-[#9ad9af] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-[#1f7a45]">
                {{ __("Blog") }}
            </span>
            <h1 class="mt-6 text-4xl font-bold leading-tight text-[#112119] md:text-6xl">
                {{ __("Operational lessons for WhatsApp marketing, support, and automation") }}
            </h1>
            <p class="mt-5 text-lg leading-8 text-[#527060]">
                {{ __("A library of practical content for teams building inbound funnels, outbound campaigns, service workflows, and repeatable WhatsApp operations.") }}
            </p>
        </div>

        <div class="mx-auto mt-10 max-w-3xl rounded-[2rem] border border-[#cfe9d8] bg-white p-4 shadow-[0_24px_80px_rgba(44,122,68,0.08)] md:p-5">
            <form action="{{ url()->current() }}" method="GET" class="flex flex-col gap-3 md:flex-row">
                <div class="relative flex-1">
                    <i class="fas fa-search pointer-events-none absolute left-5 top-1/2 -translate-y-1/2 text-[#6ea882]"></i>
                    <input
                        class="h-14 w-full rounded-full border border-[#d8ecdf] bg-[#f9fefb] pl-12 pr-5 text-[#112119] outline-none transition focus:border-[#1f7a45] focus:bg-white"
                        type="text"
                        name="keyword"
                        value="{{ request('keyword') }}"
                        placeholder="{{ __("Search topics, use cases, or workflows") }}"
                    />
                </div>
                <button type="submit" class="inline-flex h-14 items-center justify-center rounded-full bg-[#1f7a45] px-8 text-sm font-semibold text-white transition hover:bg-[#176338]">
                    {{ __("Search articles") }}
                </button>
            </form>
        </div>

        <div class="mt-14 grid gap-8 lg:grid-cols-[0.82fr_1.18fr]">
            <aside class="space-y-6">
                <div class="rounded-[2rem] border border-[#cfe9d8] bg-white p-7 shadow-[0_20px_70px_rgba(44,122,68,0.06)]">
                    <h2 class="text-xl font-bold text-[#112119]">{{ __("Collections") }}</h2>
                    <p class="mt-2 text-sm leading-7 text-[#5f7a68]">
                        {{ __("Filter the content library by category.") }}
                    </p>

                    <div class="mt-6 space-y-3">
                        <a
                            href="{{ route('blogs') }}"
                            class="flex items-center justify-between rounded-[1.2rem] border px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('blogs') ? 'border-[#1f7a45] bg-[#effaf2] text-[#1f7a45]' : 'border-[#e2f1e7] bg-[#fbfefc] text-[#365244] hover:border-[#b8dfc3] hover:text-[#1f7a45]' }}"
                        >
                            <span>{{ __("All Categories") }}</span>
                            <span>{{ $countPostBlog }}</span>
                        </a>
                        @foreach($categories as $cat)
                            <a
                                href="{{ url('blogs/'.$cat->slug) }}"
                                class="flex items-center justify-between rounded-[1.2rem] border px-4 py-3 text-sm font-semibold transition {{ Request::segment(2) == $cat->slug ? 'border-[#1f7a45] bg-[#effaf2] text-[#1f7a45]' : 'border-[#e2f1e7] bg-[#fbfefc] text-[#365244] hover:border-[#b8dfc3] hover:text-[#1f7a45]' }}"
                            >
                                <span>{{ $cat->name }}</span>
                                <span>{{ $cat->articles_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[2rem] border border-[#cfe9d8] bg-white p-7 shadow-[0_20px_70px_rgba(44,122,68,0.06)]">
                    <h2 class="text-xl font-bold text-[#112119]">{{ __("Popular tags") }}</h2>
                    <div class="mt-6 flex flex-wrap gap-3">
                        @foreach($tags as $tag)
                            <a href="{{ url('blogs/tag/'.$tag->slug) }}" class="rounded-full border border-[#cce7d5] bg-[#effaf2] px-4 py-2 text-sm font-semibold text-[#1f7a45] transition hover:border-[#1f7a45] hover:bg-white">
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>

            <div>
                <div class="grid gap-6 md:grid-cols-2">
                    @forelse($blogs as $blog)
                        <article class="flex h-full flex-col overflow-hidden rounded-[2rem] border border-[#cfe9d8] bg-white shadow-[0_20px_70px_rgba(44,122,68,0.08)] transition hover:-translate-y-1 hover:shadow-[0_28px_90px_rgba(44,122,68,0.12)]">
                            <a href="{{ route('blog.detail', $blog->slug) }}" class="block overflow-hidden">
                                <img
                                    src="{{ $blog->thumbnail ? Media::url($blog->thumbnail) : 'https://placehold.co/640x420' }}"
                                    alt="{{ $blog->title }}"
                                    class="h-60 w-full object-cover transition duration-700 hover:scale-105"
                                />
                            </a>
                            <div class="flex flex-1 flex-col p-7">
                                <div class="flex flex-wrap items-center gap-3">
                                    @if($blog->category)
                                        <span class="rounded-full bg-[#effaf2] px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#1f7a45]">
                                            {{ $blog->category->name }}
                                        </span>
                                    @endif
                                    <span class="text-sm text-[#6c8577]">
                                        {{ $blog->created ? \Carbon\Carbon::createFromTimestamp($blog->created)->format('d M Y') : '' }}
                                    </span>
                                </div>

                                <h2 class="mt-5 text-2xl font-bold leading-9 text-[#112119]">
                                    <a href="{{ route('blog.detail', $blog->slug) }}" class="transition hover:text-[#1f7a45]">
                                        {{ $blog->title }}
                                    </a>
                                </h2>

                                <p class="mt-4 flex-1 text-base leading-8 text-[#527060]">
                                    {{ Str::limit(strip_tags($blog->desc), 140) }}
                                </p>

                                <a href="{{ route('blog.detail', $blog->slug) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-[#1f7a45] transition hover:text-[#176338]">
                                    <span>{{ __("Read article") }}</span>
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="md:col-span-2">
                            <div class="rounded-[2rem] border border-dashed border-[#b9dec4] bg-white px-8 py-14 text-center shadow-[0_20px_70px_rgba(44,122,68,0.05)]">
                                <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-[#effaf2] text-2xl text-[#1f7a45]">
                                    <i class="fas fa-file-alt"></i>
                                </span>
                                <h3 class="mt-6 text-2xl font-bold text-[#112119]">{{ __("No blog posts found") }}</h3>
                                <p class="mx-auto mt-3 max-w-xl text-base leading-8 text-[#527060]">
                                    {{ __("We could not find any articles matching this filter. Reset the view and browse the full article library.") }}
                                </p>
                                <a href="{{ route('blogs') }}" class="mt-7 inline-flex items-center gap-2 rounded-full bg-[#1f7a45] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#176338]">
                                    <span>{{ __("Back to all blogs") }}</span>
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-10">
                    {!! $blogs->links('partials.pagination') !!}
                </div>
            </div>
        </div>
    </div>
</section>
