<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Jadwal Pemeliharaan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Terjadwal</p>
                    <p class="text-2xl font-bold text-blue-600 mt-auto">{{ $summary['scheduled'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Sedang Berjalan</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-auto">{{ $summary['in_progress'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Selesai</p>
                    <p class="text-2xl font-bold text-green-600 mt-auto">{{ $summary['completed'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Terlambat</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto">{{ $summary['overdue'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('head.maintenance.index')" :search-value="$search" search-placeholder="Cari judul / kode / vendor / PIC" :reset-url="route('head.maintenance.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
                <x-filter-pills name="type" label="Tipe" :options="$typeLabels" :selected="$selectedType" all-label="Semua Tipe" />
            </x-filter-bar>

            {{-- Tabel --}}
            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Pekerjaan</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Aset</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Jadwal</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Prioritas</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse ($jobs as $job)
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
                                        @if ($job->scheduled_time)
                                            <span class="text-ink-muted">{{ \Illuminate\Support\Str::of($job->scheduled_time)->substr(0, 5) }}</span>
                                        @endif
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
                                            {{ $statusLabels[$job->status] ?? $job->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-muted">
                                        Belum ada pekerjaan pemeliharaan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div><x-table-footer :paginator="$jobs" /></div>
        </div>
    </div>
</x-app-layout>
