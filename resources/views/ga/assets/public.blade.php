<x-guest-layout>
    <div class="text-center mb-4">
        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
            {{ $statusLabels[$asset->status] ?? $asset->status }}
        </span>
    </div>

    @if ($asset->image_path)
        <img src="{{ Storage::url($asset->image_path) }}" class="w-full aspect-square rounded-lg object-cover mb-4">
    @else
        <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-sm mb-4">
            Belum ada foto
        </div>
    @endif

    <h1 class="text-lg font-bold text-gray-900 text-center">{{ $asset->name }}</h1>
    <p class="text-sm font-mono text-gray-500 text-center mb-4">{{ $asset->asset_code }}</p>

    <dl class="grid grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-4">
        <div>
            <dt class="text-gray-400 text-xs">Merk / Model</dt>
            <dd class="font-medium text-gray-800">{{ $asset->brand ?: '-' }} {{ $asset->model }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Nomor Serial</dt>
            <dd class="font-medium text-gray-800">{{ $asset->serial_number ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Lokasi</dt>
            <dd class="font-medium text-gray-800">{{ $asset->location ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Outlet</dt>
            <dd class="font-medium text-gray-800">{{ $asset->outletLabel() ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">PIC</dt>
            <dd class="font-medium text-gray-800">{{ $asset->custodian_name ?: '-' }}</dd>
        </div>
    </dl>

    @if ($ongoingMaintenance->isNotEmpty())
        <div class="border-t border-gray-100 pt-4 mt-4">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Pemeliharaan Berlangsung</h2>
            <div class="space-y-2">
                @foreach ($ongoingMaintenance as $job)
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-medium text-gray-800">{{ $job->title }}</p>
                            <span class="shrink-0 inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full {{ \App\Models\GA\MaintenanceJob::statusBadgeColor($job->status) }}">
                                {{ $maintenanceStatusLabels[$job->status] ?? $job->status }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $maintenanceTypeLabels[$job->type] ?? $job->type }}
                            @if ($job->scheduled_date)
                                &middot; {{ $job->scheduled_date->translatedFormat('d M Y') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="border-t border-gray-100 pt-4 mt-4">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Riwayat Pemeliharaan</h2>
        @if ($maintenanceHistory->isNotEmpty())
            <div class="space-y-2">
                @foreach ($maintenanceHistory as $job)
                    <div class="flex items-start justify-between gap-2 text-sm">
                        <div>
                            <p class="font-medium text-gray-800">{{ $job->title }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $maintenanceTypeLabels[$job->type] ?? $job->type }}
                                @if ($job->scheduled_date)
                                    &middot; {{ $job->scheduled_date->translatedFormat('d M Y') }}
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full {{ \App\Models\GA\MaintenanceJob::statusBadgeColor($job->status) }}">
                            {{ $maintenanceStatusLabels[$job->status] ?? $job->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Belum ada pemeliharaan</p>
        @endif
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        Tampilan publik &middot; hanya-baca &middot; {{ config('app.name') }}
    </p>
</x-guest-layout>
