<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.maintenance.index')" parent="Jadwal Pemeliharaan" current="Detail Pemeliharaan" />
            <h2 class="font-semibold text-xl text-ink leading-tight">
                {{ $job->title }}
                <span class="ml-2 font-mono text-sm text-ink-muted">{{ $job->job_code }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="card-flat p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\MaintenanceJob::statusBadgeColor($job->status) }}">
                        {{ $statusLabels[$job->status] ?? $job->status }}
                    </span>
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\MaintenanceJob::priorityBadgeColor($job->priority) }}">
                        {{ $priorityLabels[$job->priority] ?? $job->priority }}
                    </span>
                    <span class="text-sm text-ink-muted">{{ $typeLabels[$job->type] ?? $job->type }}</span>
                    @if ($job->isOverdue())
                        <span class="text-sm font-medium text-red-600">Terlambat</span>
                    @endif
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-ink-muted">Aset</dt>
                        <dd class="text-ink font-medium">
                            @if ($job->asset)
                                <a href="{{ route('outlet.assets.show', $job->asset) }}" class="text-action hover:underline">
                                    {{ $job->asset->asset_code }} — {{ $job->asset->name }}
                                </a>
                            @else — @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Jadwal</dt>
                        <dd class="text-ink font-medium">
                            {{ optional($job->scheduled_date)->format('d M Y') }}
                            @if ($job->scheduled_time)
                                {{ \Illuminate\Support\Str::of($job->scheduled_time)->substr(0, 5) }}
                                @if ($job->scheduled_end_time)
                                    – {{ \Illuminate\Support\Str::of($job->scheduled_end_time)->substr(0, 5) }}
                                @endif
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">PIC</dt>
                        <dd class="text-ink font-medium">{{ $job->pic_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Vendor</dt>
                        <dd class="text-ink font-medium">{{ $job->vendor_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Lokasi</dt>
                        <dd class="text-ink font-medium">{{ $job->location ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($job->notes)
                    <div class="mt-4 text-sm">
                        <p class="text-ink-muted">Catatan</p>
                        <p class="text-ink whitespace-pre-line">{{ $job->notes }}</p>
                    </div>
                @endif
            </div>

            @if (!empty($job->checklist))
                <div class="card-flat p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-medium text-ink">Checklist</h3>
                        <span class="text-sm text-ink-muted">{{ $job->checklistProgress() }}% selesai</span>
                    </div>
                    <div class="w-full bg-surface rounded-full h-2 mb-4">
                        <div class="bg-action h-2 rounded-full" style="width: {{ $job->checklistProgress() }}%"></div>
                    </div>
                    <ul class="space-y-2 text-sm">
                        @foreach ($job->checklist as $item)
                            <li class="flex items-center gap-2">
                                <span class="{{ ($item['done'] ?? false) ? 'text-green-600' : 'text-ink-muted' }}">
                                    {{ ($item['done'] ?? false) ? '✓' : '○' }}
                                </span>
                                <span class="{{ ($item['done'] ?? false) ? 'line-through text-ink-muted' : 'text-ink' }}">
                                    {{ $item['text'] ?? '' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($job->status === \App\Models\GA\MaintenanceJob::STATUS_COMPLETED)
                <div class="bg-green-50 border border-green-200 shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-green-800 mb-1">Pekerjaan Selesai</h3>
                    <p class="text-sm text-green-700">
                        Diselesaikan {{ optional($job->completed_at)->format('d M Y H:i') }}
                    </p>
                    @if ($job->completion_notes)
                        <p class="mt-2 text-sm text-green-800 whitespace-pre-line">{{ $job->completion_notes }}</p>
                    @endif
                </div>
            @endif

            <div>
                <a href="{{ route('outlet.maintenance.index') }}" class="text-sm text-ink-muted hover:text-ink">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
