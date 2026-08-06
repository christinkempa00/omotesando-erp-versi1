{{--
    Bar chart horizontal sederhana, murni CSS (tanpa JS chart library) —
    konsisten dengan pendekatan donut-chart.blade.php.

    Props:
    - $data: array of ['label' => string, 'value' => number]
    - $color: warna bar, default indigo
--}}
@props(['data', 'color' => '#6366f1'])

@php
    $max = collect($data)->max('value') ?: 1;
@endphp

<div class="space-y-2.5">
    @forelse ($data as $row)
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-500">{{ $row['label'] }}</span>
                <span class="font-medium text-gray-700">Rp {{ number_format($row['value'], 0, ',', '.') }}</span>
            </div>
            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full" style="width: {{ $max > 0 ? min(100, ($row['value'] / $max) * 100) : 0 }}%; background: {{ $color }}"></div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">Belum ada data.</p>
    @endforelse
</div>
