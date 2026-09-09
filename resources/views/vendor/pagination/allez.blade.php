@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $window = 1; // tetangga kiri-kanan current

    if ($last <= 7) {
        // Sedikit halaman -- tampilkan semua nomor, tanpa ellipsis.
        $pages = collect(range(1, $last));
    } else {
        $pages = collect([1]);
        for ($i = $current - $window; $i <= $current + $window; $i++) {
            if ($i > 1 && $i < $last) {
                $pages->push($i);
            }
        }
        $pages->push($last);
        $pages = $pages->unique()->sort()->values();
    }
@endphp

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

        {{-- Numbers + ellipsis (deteksi gap) --}}
        @php $prev = 0; @endphp
        @foreach ($pages as $page)
            @if ($page - $prev > 1)
                <span class="px-2 text-ink-muted">&hellip;</span>
            @endif

            @if ($page == $current)
                <span aria-current="page"
                      class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg text-sm font-mono bg-gold-50 text-gold-700 border border-gold-200">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                   class="min-w-9 h-9 px-2 inline-flex items-center justify-center rounded-lg border border-hairline text-sm font-mono text-ink hover:bg-surface transition">
                    {{ $page }}
                </a>
            @endif

            @php $prev = $page; @endphp
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
