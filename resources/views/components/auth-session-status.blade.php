@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-ink bg-surface border border-hairline rounded-lg px-3 py-2']) }}>
        {{ $status }}
    </div>
@endif
