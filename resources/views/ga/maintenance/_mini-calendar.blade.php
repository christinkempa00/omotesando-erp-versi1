@php
    $firstOfMonth = $calendarMonth->copy()->startOfMonth();
    $gridStart = $firstOfMonth->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);
    $gridEnd = $firstOfMonth->copy()->endOfMonth()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY);
    $today = now()->toDateString();
@endphp

<div class="glass-panel p-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">{{ $calendarMonth->translatedFormat('F Y') }}</h3>
        <div class="flex items-center gap-1">
            <a href="{{ route('ga.maintenance.index', array_filter(request()->except('month') + ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')])) }}"
               class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:bg-gray-100">‹</a>
            <a href="{{ route('ga.maintenance.index', array_filter(request()->except('month') + ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')])) }}"
               class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:bg-gray-100">›</a>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-400 mb-1">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)
            <span>{{ $d }}</span>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        @php $cursor = $gridStart->copy(); @endphp
        @while ($cursor->lte($gridEnd))
            @php
                $dateStr = $cursor->toDateString();
                $inMonth = $cursor->month === $firstOfMonth->month;
                $isToday = $dateStr === $today;
                $dateJobIds = $jobIdsByDate[$dateStr] ?? collect();
                $hasJobs = $dateJobIds->isNotEmpty();
            @endphp
            <button type="button"
                    @if ($hasJobs) @click="onDateClick('{{ $dateStr }}', {{ Illuminate\Support\Js::from($dateJobIds) }})" @endif
                    class="relative aspect-square flex items-center justify-center text-xs rounded-full
                           {{ $isToday ? 'bg-gold-500 text-white font-semibold' : ($inMonth ? 'text-gray-700' : 'text-gray-300') }}
                           {{ $hasJobs ? 'cursor-pointer hover:bg-gold-50 hover:text-gold-700' : 'cursor-default' }}">
                {{ $cursor->day }}
                @if ($hasJobs && ! $isToday)
                    <span class="absolute bottom-0.5 w-1 h-1 rounded-full bg-orange-400"></span>
                @endif
            </button>
            @php $cursor->addDay(); @endphp
        @endwhile
    </div>
</div>
