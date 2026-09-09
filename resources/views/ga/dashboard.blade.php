<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kartu ringkasan utama — angka spesifik yang paling relevan
                 buat dilihat sekilas (bukan breakdown kondisi/status, itu
                 sudah ada di section detail di bawah). --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
                <div class="card-flat flex items-center gap-4 p-6">
                    <span class="w-11 h-11 flex-none rounded-[10px] bg-gold-500 flex items-center justify-center">
                        <svg class="w-[22px] h-[22px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="7" width="16" height="13" rx="1.5" /><path d="M4 7l2-3h12l2 3" /><path d="M10 11h4" />
                        </svg>
                    </span>
                    <div class="flex flex-col gap-0.5">
                        <p class="text-xs font-medium text-ink-muted">Total Aset</p>
                        <p class="font-mono text-[28px] font-medium text-ink">{{ $assetTotal }}</p>
                    </div>
                </div>
                <div class="card-flat flex items-center gap-4 p-6">
                    <span class="w-11 h-11 flex-none rounded-[10px] bg-gold-500 flex items-center justify-center">
                        <svg class="w-[22px] h-[22px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="6" y="4" width="12" height="16" rx="2" /><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" /><path d="M9 10h6M9 14h6M9 18h3" />
                        </svg>
                    </span>
                    <div class="flex flex-col gap-0.5">
                        <p class="text-xs font-medium text-ink-muted">Total Pengajuan</p>
                        <p class="font-mono text-[28px] font-medium text-ink">{{ $gaRequestTotal }}</p>
                    </div>
                </div>
                <div class="card-flat flex items-center gap-4 p-6">
                    <span class="w-11 h-11 flex-none rounded-[10px] bg-gold-500 flex items-center justify-center">
                        <svg class="w-[22px] h-[22px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                        </svg>
                    </span>
                    <div class="flex flex-col gap-0.5">
                        <p class="text-xs font-medium text-ink-muted">Total Biaya Pemeliharaan</p>
                        <p class="font-mono text-[28px] font-medium text-ink">Rp {{ number_format($totalMaintenanceCost, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Kondisi inventaris terbaru: Aset & Seragam --}}
            <div class="card-flat p-6">
                <h3 class="text-sm font-semibold text-ink mb-5">Kondisi Inventaris Terbaru</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <p class="text-xs font-medium text-ink-muted uppercase tracking-wider mb-3">Aset</p>
                        @include('ga.partials.status-donut', ['data' => $assetByStatus, 'labels' => $assetStatusLabels, 'total' => $assetTotal])
                        <a href="{{ route('ga.assets.index') }}" class="mt-4 inline-block text-sm text-action hover:text-action-hover">
                            Lihat semua aset &rarr;
                        </a>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-ink-muted uppercase tracking-wider mb-3">Seragam</p>
                        @include('ga.partials.status-donut', ['data' => $uniformByStatus, 'labels' => $uniformStatusLabels, 'total' => $uniformTotal])
                        <a href="{{ route('ga.uniforms.stocks.index') }}" class="mt-4 inline-block text-sm text-action hover:text-action-hover">
                            Lihat semua seragam &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Ringkasan pemeliharaan --}}
            <div class="card-flat p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">Pemeliharaan</h3>
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 h-6 px-2.5 rounded-full border border-status-received-fg/30 bg-status-received-bg text-status-received-fg text-xs font-semibold">Terjadwal {{ $maintenanceSummary['scheduled'] }}</span>
                    </div>
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 h-6 px-2.5 rounded-full border border-status-pending-fg/30 bg-status-pending-bg text-status-pending-fg text-xs font-semibold">Sedang Berjalan {{ $maintenanceSummary['in_progress'] }}</span>
                    </div>
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 h-6 px-2.5 rounded-full border border-status-approved-fg/30 bg-status-approved-bg text-status-approved-fg text-xs font-semibold">Selesai {{ $maintenanceSummary['completed'] }}</span>
                    </div>
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 h-6 px-2.5 rounded-full border border-status-rejected-fg/30 bg-status-rejected-bg text-status-rejected-fg text-xs font-semibold">Terlambat {{ $maintenanceSummary['overdue'] }}</span>
                    </div>
                </div>
                <a href="{{ route('ga.maintenance.index') }}" class="mt-4 inline-block text-sm text-action hover:text-action-hover">
                    Lihat semua pemeliharaan &rarr;
                </a>
            </div>

            {{-- Jadwal pemeliharaan mendatang --}}
            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-ink">Jadwal Pemeliharaan Mendatang</h3>
                    <a href="{{ route('ga.maintenance.create') }}" class="btn-primary">Buat Pemeliharaan</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Pekerjaan</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Aset</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Jadwal</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Prioritas</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                                <th class="px-4 py-3 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse ($upcomingJobs as $job)
                                <tr class="hover:bg-[#FDFBF4]">
                                    <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $job->job_code }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-ink">{{ $job->title }}</div>
                                        <div class="text-xs text-ink-muted">{{ $typeLabels[$job->type] ?? $job->type }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">
                                        {{ $job->asset?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">
                                        {{ optional($job->scheduled_date)->format('d M Y') }}
                                        @if ($job->isOverdue())
                                            <span class="ml-1 text-xs font-medium text-red-600">(terlambat)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\MaintenanceJob::priorityBadgeColor($job->priority) }}">
                                            {{ \App\Models\GA\MaintenanceJob::priorityLabels()[$job->priority] ?? $job->priority }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\MaintenanceJob::statusBadgeColor($job->status) }}">
                                            {{ \App\Models\GA\MaintenanceJob::statusLabels()[$job->status] ?? $job->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('ga.maintenance.show', $job) }}" class="text-action hover:text-action-hover">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-ink-muted">
                                        Tidak ada pemeliharaan dalam 7 hari ke depan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Rincian per outlet: gabungan Aset, Seragam, & Pemeliharaan --}}
            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline">
                    <h3 class="text-sm font-semibold text-ink">Rincian Per Outlet</h3>
                    <p class="text-xs text-ink-muted mt-0.5">Ringkasan aset, seragam, dan pemeliharaan per outlet</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Outlet</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Total Aset</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Stok Seragam</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Terjadwal</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Selesai</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Biaya Pemeliharaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @foreach ($outletBreakdown as $row)
                                <tr class="hover:bg-[#FDFBF4]">
                                    <td class="px-4 py-3 font-medium text-ink">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink">{{ $row['total_assets'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink">{{ $row['total_uniform_stock'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink font-medium">{{ $row['maintenance_scheduled'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink font-medium">{{ $row['maintenance_completed'] }}</td>
                                    <td class="px-4 py-3 text-right text-ink">Rp {{ number_format($row['maintenance_cost'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Riwayat pemeliharaan --}}
            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline">
                    <h3 class="text-sm font-semibold text-ink">Riwayat Pemeliharaan</h3>
                    <p class="text-xs text-ink-muted mt-0.5">Data dari jadwal pemeliharaan yang sudah selesai</p>
                </div>
                <div class="divide-y divide-hairline">
                    @forelse ($maintenanceHistory as $job)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-ink truncate">{{ $job->title }}</p>
                                <p class="text-xs text-ink-muted truncate">
                                    {{ $typeLabels[$job->type] ?? $job->type }} &middot; {{ $job->outletLabel() ?? '—' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0 text-right">
                                <span class="text-xs text-ink-muted">{{ optional($job->completed_at)->format('Y-m-d') }}</span>
                                <span class="font-medium text-ink">
                                    {{ $job->cost !== null ? 'Rp '.number_format((float) $job->cost, 0, ',', '.') : '—' }}
                                </span>
                                <a href="{{ route('ga.maintenance.show', $job) }}" class="text-action hover:underline">Selesai</a>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada pemeliharaan yang selesai.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
