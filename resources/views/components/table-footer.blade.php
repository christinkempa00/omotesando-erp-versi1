@props(['paginator', 'perPageOptions' => [10, 25, 50, 100]])
@if ($paginator->total() > 0)
<div class="px-4 py-3 border-t border-hairline space-y-3">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="text-sm text-ink-muted">
            Menampilkan
            <span class="font-mono text-ink">{{ $paginator->firstItem() }}</span>–<span class="font-mono text-ink">{{ $paginator->lastItem() }}</span>
            dari <span class="font-mono text-ink">{{ $paginator->total() }}</span>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-1.5">
                @foreach (request()->except(['per_page', 'page']) as $key => $val)
                    @if (is_array($val))
                        @foreach ($val as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <select name="per_page" onchange="this.form.submit()"
                    class="h-9 rounded-lg border-hairline text-sm text-ink focus:border-action focus:ring-action">
                    @foreach ($perPageOptions as $opt)
                        <option value="{{ $opt }}" @selected(request('per_page', $paginator->perPage()) == $opt)>{{ $opt }} per halaman</option>
                    @endforeach
                </select>
            </form>
            <div>{{ $paginator->onEachSide(2)->links('vendor.pagination.allez') }}</div>
        </div>
    </div>
    @isset($export)
        <div class="flex justify-end">{{ $export }}</div>
    @endisset
</div>
@endif
