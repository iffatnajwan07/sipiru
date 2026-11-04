@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3 text-sm" aria-label="Pagination">
        <p class="hidden text-slate-500 sm:block">
            Menampilkan <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
            dari <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm opacity-50"><x-icon name="chevron-left" class="size-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-secondary btn-sm" rel="prev"><x-icon name="chevron-left" class="size-4" /></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-slate-400">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-primary btn-sm min-w-8">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-ghost btn-sm hidden min-w-8 sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-secondary btn-sm" rel="next"><x-icon name="chevron-right" class="size-4" /></a>
            @else
                <span class="btn btn-secondary btn-sm opacity-50"><x-icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
