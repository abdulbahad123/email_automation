@php
    $countPostBlog = Home::countPostBlog();
    $recentPlogs = Home::getRecentBlogs();
    $categories = Home::getBlogCategories();
    $tags = Home::getBlogTags();
    $blogDetail = Home::getBlogDetail();
@endphp

@section('pagetitle', $blogDetail->title)

<section class="bg-[#f3fbf6] py-20 md:py-28">
    <div class="container mx-auto px-4">
        <div class="mx-auto max-w-4xl text-center">
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if($blogDetail->category)
                    <span class="rounded-full bg-[#effaf2] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#1f7a45]">
                        {{ $blogDetail->category->name }}
                    </span>
                @endif
                @foreach($blogDetail->tags as $tag)
                    <span class="rounded-full border border-[#cce7d5] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#1f7a45]">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>

            <h1 class="mt-6 text-4xl font-bold leading-tight text-[#112119] md:text-6xl">
                {{ $blogDetail->title }}
            </h1>
            <p class="mt-5 text-sm font-medium uppercase tracking-[0.2em] text-[#6c8577]">
                {{ __("Published") }} {{ $blogDetail->created ? \Carbon\Carbon::createFromTimestamp($blogDetail->created)->format('d M Y') : '' }}
            </p>
        </div>

        <div class="mt-14 grid gap-8 lg:grid-cols-[1.18fr_0.82fr]">
            <article class="order-2 overflow-hidden rounded-[2rem] border border-[#cfe9d8] bg-white shadow-[0_24px_80px_rgba(44,122,68,0.08)] lg:order-1">
                <img
                    src="{{ $blogDetail->thumbnail ? Media::url($blogDetail->thumbnail) : 'https://placehold.co/1200x680' }}"
                    alt="{{ $blogDetail->title }}"
                    class="h-[320px] w-full object-cover md:h-[420px]"
                />

                <div class="p-7 md:p-10">
                    <div class="prose-content max-w-none text-base leading-8 text-[#41584b]">
                        {!! $blogDetail->content !!}
                    </div>

                    @if($blogDetail->tags && count($blogDetail->tags))
                        <div class="mt-10 border-t border-[#e0f0e5] pt-6">
                            <h3 class="text-lg font-bold text-[#112119]">{{ __("Related tags") }}</h3>
                            <div class="mt-4 flex flex-wrap gap-3">
                                @foreach($blogDetail->tags as $tag)
                                    @if(!empty($tag->slug))
                                        <a href="{{ route('blog.tag', ['tag_slug' => $tag->slug]) }}" class="rounded-full border border-[#cce7d5] bg-[#effaf2] px-4 py-2 text-sm font-semibold text-[#1f7a45] transition hover:border-[#1f7a45] hover:bg-white">
                                            #{{ $tag->name }}
                                        </a>
                                    @else
                                        <span class="rounded-full border border-[#e0ebe4] bg-[#f6faf7] px-4 py-2 text-sm font-semibold text-[#7a8d81]">
                                            #{{ $tag->name }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </article>

            <aside class="order-1 space-y-6 lg:order-2">
                <div class="rounded-[2rem] border border-[#cfe9d8] bg-white p-7 shadow-[0_20px_70px_rgba(44,122,68,0.06)]">
                    <h2 class="text-xl font-bold text-[#112119]">{{ __("Collections") }}</h2>
                    <div class="mt-6 space-y-3">
                        <a href="{{ route('blogs') }}" class="flex items-center justify-between rounded-[1.2rem] border border-[#e2f1e7] bg-[#fbfefc] px-4 py-3 text-sm font-semibold text-[#365244] transition hover:border-[#b8dfc3] hover:text-[#1f7a45]">
                            <span>{{ __("All Categories") }}</span>
                            <span>{{ $countPostBlog }}</span>
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ url('blogs/'.$cat->slug) }}" class="flex items-center justify-between rounded-[1.2rem] border border-[#e2f1e7] bg-[#fbfefc] px-4 py-3 text-sm font-semibold text-[#365244] transition hover:border-[#b8dfc3] hover:text-[#1f7a45]">
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

                <div class="rounded-[2rem] border border-[#cfe9d8] bg-white p-7 shadow-[0_20px_70px_rgba(44,122,68,0.06)]">
                    <h2 class="text-xl font-bold text-[#112119]">{{ __("Recent posts") }}</h2>
                    <div class="mt-6 space-y-4">
                        @foreach($recentPlogs as $blog)
                            <a href="{{ route('blog.detail', $blog->slug) }}" class="group flex gap-4 rounded-[1.4rem] border border-[#edf6ef] bg-[#fbfefc] p-3 transition hover:border-[#b8dfc3] hover:bg-white">
                                <img
                                    src="{{ $blog->thumbnail ? Media::url($blog->thumbnail) : 'https://placehold.co/120x120' }}"
                                    alt="{{ $blog->title }}"
                                    class="h-20 w-20 rounded-2xl object-cover"
                                />
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6ea882]">
                                        {{ $blog->created ? \Carbon\Carbon::createFromTimestamp($blog->created)->format('d M Y') : '' }}
                                    </p>
                                    <h3 class="mt-2 text-sm font-bold leading-6 text-[#112119] transition group-hover:text-[#1f7a45]">
                                        {{ $blog->title }}
                                    </h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
