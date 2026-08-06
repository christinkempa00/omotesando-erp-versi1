{{--
    Donut chart proporsi, murni CSS (conic-gradient), tanpa JS chart library —
    versi generik dari ga/partials/status-donut.blade.php supaya bisa dipakai
    modul mana pun (bukan cuma status Aset).

    Props:
    - $data: array/collection [key => count]
    - $labels: array/collection [key => label], urutan menentukan urutan legenda & irisan
    - $colors: array [key => hex], opsional — kalau tidak diisi pakai palet default
    - $total: int, total keseluruhan (kalau 0, tampilkan lingkaran abu-abu kosong)
    - $size: kelas Tailwind ukuran lingkaran, default w-24 h-24
--}}
@props(['data', 'labels', 'colors' => [], 'total', 'size' => 'w-24 h-24'])

@php
    $palette = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6'];
    $resolvedColors = [];
    $i = 0;
    foreach ($labels as $value => $label) {
        $resolvedColors[$value] = $colors[$value] ?? $palette[$i % count($palette)];
        $i++;
    }

    $stops = [];
    $cursor = 0;
    foreach ($labels as $value => $label) {
        $count = $data[$value] ?? 0;
        if ($total > 0 && $count > 0) {
            $start = $cursor;
            $end = $cursor + ($count / $total) * 360;
            $stops[] = $resolvedColors[$value]." {$start}deg {$end}deg";
            $cursor = $end;
        }
    }
    $gradient = count($stops) ? 'conic-gradient('.implode(', ', $stops).')' : '#e5e7eb';
@endphp

<div class="flex items-center gap-4">
    <div class="{{ $size }} rounded-full shrink-0 relative" style="background: {{ $gradient }}">
        <div class="absolute inset-2.5 bg-white rounded-full flex items-center justify-center">
            <span class="text-base font-bold text-gray-800">{{ $total }}</span>
        </div>
    </div>
    <div class="space-y-1 text-xs">
        @foreach ($labels as $value => $label)
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $resolvedColors[$value] }}"></span>
                <span class="text-gray-600">{{ $label }}</span>
                <span class="font-medium text-gray-800">{{ $data[$value] ?? 0 }}</span>
            </div>
        @endforeach
    </div>
</div>
