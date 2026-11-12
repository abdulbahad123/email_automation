@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $showPages = 2;
    @endphp

    <div class="flex flex-wrap justify-center items-center gap-2">
        @if ($currentPage == 1)
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#dceee2] bg-white text-[#b6c7bc]">
                <i class="fas fa-chevron-left text-xs"></i>
            </span>
        @else
            <a href="{{ $paginator->url($currentPage - 1) }}" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#cce7d5] bg-[#effaf2] text-[#1f7a45] transition hover:border-[#1f7a45] hover:bg-white" aria-label="Previous">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
        @endif

        @if ($currentPage > $showPages + 1)
            <a href="{{ $paginator->url(1) }}" class="inline-flex h-11 min-w-[44px] items-center justify-center rounded-full border border-[#dceee2] bg-white px-4 text-sm font-semibold text-[#365244] transition hover:border-[#b8dfc3] hover:text-[#1f7a45]">
                1
            </a>
            @if ($currentPage > $showPages + 2)
                <span class="px-2 text-sm font-semibold text-[#86a392]">...</span>
            @endif
        @endif

        @for ($i = max(1, $currentPage - $showPages); $i <= min($lastPage, $currentPage + $showPages); $i++)
            @if ($i == $currentPage)
                <span class="inline-flex h-11 min-w-[44px] items-center justify-center rounded-full bg-gradient-to-r from-[#1f7a45] to-[#2ea05d] px-4 text-sm font-semibold text-white shadow-[0_12px_30px_rgba(31,122,69,0.22)]">
                    {{ $i }}
                </span>
            @else
                <a href="{{ $paginator->url($i) }}" class="inline-flex h-11 min-w-[44px] items-center justify-center rounded-full border border-[#dceee2] bg-white px-4 text-sm font-semibold text-[#365244] transition hover:border-[#b8dfc3] hover:text-[#1f7a45]">
                    {{ $i }}
                </a>
            @endif
        @endfor

        @if ($currentPage < $lastPage - $showPages)
            @if ($currentPage < $lastPage - $showPages - 1)
                <span class="px-2 text-sm font-semibold text-[#86a392]">...</span>
            @endif
            <a href="{{ $paginator->url($lastPage) }}" class="inline-flex h-11 min-w-[44px] items-center justify-center rounded-full border border-[#dceee2] bg-white px-4 text-sm font-semibold text-[#365244] transition hover:border-[#b8dfc3] hover:text-[#1f7a45]">
                {{ $lastPage }}
            </a>
        @endif

        @if ($currentPage == $lastPage)
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#dceee2] bg-white text-[#b6c7bc]">
                <i class="fas fa-chevron-right text-xs"></i>
            </span>
        @else
            <a href="{{ $paginator->url($currentPage + 1) }}" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#cce7d5] bg-[#effaf2] text-[#1f7a45] transition hover:border-[#1f7a45] hover:bg-white" aria-label="Next">
                <i class="fas fa-chevron-right text-xs"></i>
            </a>
        @endif
    </div>
@endif
