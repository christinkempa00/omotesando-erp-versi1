@props(['href', 'parent' => null, 'current' => null])
<div class="mb-2 flex items-center gap-1.5 text-xs font-medium text-ink-muted">
    <a href="{{ $href }}" class="inline-flex items-center gap-1 hover:text-ink transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 18l-6-6 6-6" />
        </svg>
        {{ $parent ?? 'Kembali' }}
    </a>
    @if ($current)
        <span class="text-ink-muted/60">/</span>
        <span class="text-ink">{{ $current }}</span>
    @endif
</div>
