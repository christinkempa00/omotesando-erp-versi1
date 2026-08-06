{{--
    Donut chart proporsi status murni CSS (conic-gradient), tanpa JS chart
    library. Props:
    - $data: array/collection [status_value => count]
    - $labels: array/collection [status_value => label]
    - $total: int, total keseluruhan (kalau 0, tampilkan state kosong)
--}}
@php
    $colors = [
        'bagus' => '#22c55e',
        'dalam_pemeliharaan' => '#eab308',
        'rusak' => '#ef4444',
    ];

    $stops = [];
    $cursor = 0;
    foreach ($labels as $value => $label) {
        $count = $data[$value] ?? 0;
        if ($total > 0 && $count > 0) {
            $start = $cursor;
            $end = $cursor + ($count / $total) * 360;
            $stops[] = ($colors[$value] ?? '#9ca3af')." {$start}deg {$end}deg";
            $cursor = $end;
        }
    }
    $gradient = count($stops) ? 'conic-gradient('.implode(', ', $stops).')' : '#e5e7eb';
@endphp

<div class="flex items-center gap-6">
    <div class="w-28 h-28 rounded-full shrink-0 relative" style="background: {{ $gradient }}">
        <div class="absolute inset-3 bg-white rounded-full flex items-center justify-center">
            <span class="text-lg font-bold text-gray-800">{{ $total }}</span>
        </div>
    </div>
    <div class="space-y-1.5 text-sm">
        @foreach ($labels as $value => $label)
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $colors[$value] ?? '#9ca3af' }}"></span>
                <span class="text-gray-600">{{ $label }}</span>
                <span class="font-medium text-gray-800">{{ $data[$value] ?? 0 }}</span>
            </div>
        @endforeach
    </div>
</div>
