@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="inline-flex items-center gap-1">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-hidden="true"
                  class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg border border-hairline text-ink-muted opacity-40 cursor-default pointer-events-none">
                &lsaquo;
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
               class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg border border-hairline text-ink hover:bg-surface transition">
                &lsaquo;
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="px-2 text-ink-muted">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg text-sm font-mono bg-gold-50 text-gold-700 border border-gold-200">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                           class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg border border-hairline text-sm font-mono text-ink hover:bg-surface transition">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
               class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg border border-hairline text-ink hover:bg-surface transition">
                &rsaquo;
            </a>
        @else
            <span aria-disabled="true" aria-hidden="true"
                  class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg border border-hairline text-ink-muted opacity-40 cursor-default pointer-events-none">
                &rsaquo;
            </span>
        @endif
    </nav>
@endif
