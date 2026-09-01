<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kartu ringkasan utama — angka spesifik yang paling relevan
                 buat dilihat sekilas (bukan breakdown kondisi/status, itu
                 sudah ada di section detail di bawah). --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
                <div class="stat-card">
                    <span class="stat-icon">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="7" width="16" height="13" rx="1.5" /><path d="M4 7l2-3h12l2 3" /><path d="M10 11h4" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-ink-muted">Total Aset</p>
                        <p class="text-2xl font-extrabold font-mono text-ink">{{ $assetTotal }}</p>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="6" y="4" width="12" height="16" rx="2" /><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" /><path d="M9 10h6M9 14h6M9 18h3" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-ink-muted">Total Pengajuan</p>
                        <p class="text-2xl font-extrabold font-mono text-ink">{{ $gaRequestTotal }}</p>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-ink-muted">Total Biaya Pemeliharaan</p>
                        <p class="text-xl font-extrabold font-mono text-ink">Rp {{ number_format($totalMaintenanceCost, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Kondisi inventaris terbaru: Aset & Seragam --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-5">Kondisi Inventaris Terbaru</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Aset</p>
                        @include('ga.partials.status-donut', ['data' => $assetByStatus, 'labels' => $assetStatusLabels, 'total' => $assetTotal])
                        <a href="{{ route('ga.assets.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-800">
                            Lihat semua aset &rarr;
                        </a>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Seragam</p>
                        @include('ga.partials.status-donut', ['data' => $uniformByStatus, 'labels' => $uniformStatusLabels, 'total' => $uniformTotal])
                        <a href="{{ route('ga.uniforms.stocks.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-800">
                            Lihat semua seragam &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- Ringkasan pemeliharaan --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Pemeliharaan</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div class="flex items-center justify-between sm:block sm:space-y-1">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Terjadwal</span>
                        <span class="font-medium text-gray-700">{{ $maintenanceSummary['scheduled'] }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:block sm:space-y-1">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Sedang Berjalan</span>
                        <span class="font-medium text-gray-700">{{ $maintenanceSummary['in_progress'] }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:block sm:space-y-1">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Selesai</span>
                        <span class="font-medium text-gray-700">{{ $maintenanceSummary['completed'] }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:block sm:space-y-1">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Terlambat</span>
                        <span class="font-medium text-gray-700">{{ $maintenanceSummary['overdue'] }}</span>
                    </div>
                </div>
                <a href="{{ route('ga.maintenance.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-800">
                    Lihat semua pemeliharaan &rarr;
                </a>
            </div>

            {{-- Jadwal pemeliharaan mendatang --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Jadwal Pemeliharaan Mendatang</h3>
                    <a href="{{ route('ga.maintenance.create') }}" class="btn-gold">Buat Pemeliharaan</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Pekerjaan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Aset</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Jadwal</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Prioritas</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($upcomingJobs as $job)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $job->job_code }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800">{{ $job->title }}</div>
                                        <div class="text-xs text-gray-500">{{ $typeLabels[$job->type] ?? $job->type }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $job->asset?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
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
                                        <a href="{{ route('ga.maintenance.show', $job) }}" class="text-indigo-600 hover:text-indigo-800">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                        Tidak ada pemeliharaan dalam 7 hari ke depan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Rincian per outlet: gabungan Aset, Seragam, & Pemeliharaan --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Rincian Per Outlet</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Ringkasan aset, seragam, dan pemeliharaan per outlet</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Outlet</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Total Aset</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Stok Seragam</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Terjadwal</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Selesai</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Biaya Pemeliharaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($outletBreakdown as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $row['total_assets'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">{{ $row['total_uniform_stock'] }}</td>
                                    <td class="px-4 py-3 text-right text-blue-600 font-medium">{{ $row['maintenance_scheduled'] }}</td>
                                    <td class="px-4 py-3 text-right text-green-600 font-medium">{{ $row['maintenance_completed'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['maintenance_cost'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Riwayat pemeliharaan --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Riwayat Pemeliharaan</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Data dari jadwal pemeliharaan yang sudah selesai</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($maintenanceHistory as $job)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate">{{ $job->title }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ $typeLabels[$job->type] ?? $job->type }} &middot; {{ $job->outletLabel() ?? '—' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0 text-right">
                                <span class="text-xs text-gray-400">{{ optional($job->completed_at)->format('Y-m-d') }}</span>
                                <span class="font-medium text-gray-700">
                                    {{ $job->cost !== null ? 'Rp '.number_format((float) $job->cost, 0, ',', '.') : '—' }}
                                </span>
                                <a href="{{ route('ga.maintenance.show', $job) }}" class="text-indigo-600 hover:underline">Selesai</a>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-500">Belum ada pemeliharaan yang selesai.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
