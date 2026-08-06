@php
    $hours = range(7, 20); // 07:00 - 20:00
    $days = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
    $today = now()->toDateString();

    // Kelompokkan job per (hari ISO 1-7, jam) supaya beberapa job di slot yang
    // sama tersusun rapi dalam satu sel, bukan tumpang tindih.
    $jobsBySlot = [];
    foreach ($weekJobs as $job) {
        $dayIso = $job->scheduled_date->dayOfWeekIso; // 1 = Senin ... 7 = Minggu
        $hour = $job->scheduled_time ? (int) substr($job->scheduled_time, 0, 2) : 7;
        $hour = max(7, min(20, $hour));
        $jobsBySlot[$dayIso][$hour][] = $job;
    }
@endphp

<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">
            {{ $weekStart->translatedFormat('d M') }} – {{ $weekEnd->translatedFormat('d M Y') }}
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('ga.maintenance.index', array_filter(request()->except(['week', 'month']) + ['week' => now()->toDateString()])) }}"
               class="px-2.5 py-1 text-xs rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">Hari Ini</a>
            <a href="{{ route('ga.maintenance.index', array_filter(request()->except(['week', 'month']) + ['week' => $weekStart->copy()->subWeek()->toDateString()])) }}"
               class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:bg-gray-100">‹</a>
            <a href="{{ route('ga.maintenance.index', array_filter(request()->except(['week', 'month']) + ['week' => $weekStart->copy()->addWeek()->toDateString()])) }}"
               class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:bg-gray-100">›</a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <div class="grid min-w-[860px]" style="grid-template-columns: 56px repeat(7, minmax(110px,1fr));">
            {{-- Header hari --}}
            <div></div>
            @foreach ($days as $day)
                <div class="px-2 py-2 text-center border-b border-l border-gray-100">
                    <p class="text-[11px] text-gray-400 uppercase">{{ $day->translatedFormat('D') }}</p>
                    <p class="text-sm font-semibold {{ $day->toDateString() === $today ? 'text-indigo-600' : 'text-gray-700' }}">
                        {{ $day->day }}
                    </p>
                </div>
            @endforeach

            {{-- Baris per jam --}}
            @foreach ($hours as $hour)
                <div class="px-2 py-1 text-right text-[11px] text-gray-400 border-t border-gray-100" style="min-height: 64px;">
                    {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                </div>
                @foreach ($days as $day)
                    @php $dayIso = $day->dayOfWeekIso; @endphp
                    <div class="border-t border-l border-gray-100 p-1 space-y-1 {{ $day->toDateString() === $today ? 'bg-indigo-50/30' : '' }}" style="min-height: 64px;">
                        @foreach (($jobsBySlot[$dayIso][$hour] ?? []) as $job)
                            <button type="button" @click="selectedJob = jobsData[{{ $job->id }}]"
                               class="block w-full text-left rounded-md px-2 py-1 text-[11px] leading-tight border
                                      {{ \App\Models\GA\MaintenanceJob::priorityBadgeColor($job->priority) }}
                                      {{ $job->priority === 'emergency' ? 'border-red-200' : ($job->priority === 'high' ? 'border-orange-200' : 'border-slate-200') }}">
                                <p class="font-semibold truncate">{{ $job->title }}</p>
                                <p class="truncate opacity-80">
                                    {{ $job->asset?->name ?: (\App\Models\GA\MaintenanceJob::typeLabels()[$job->type] ?? '') }}
                                    @if ($job->scheduled_time)
                                        · {{ \Illuminate\Support\Str::of($job->scheduled_time)->substr(0, 5) }}
                                    @endif
                                </p>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
