<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Jadwal Pemeliharaan — {{ auth()->user()->branch?->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Terjadwal</p>
                    <p class="text-2xl font-bold text-blue-600 mt-auto">{{ $summary['scheduled'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Sedang Berjalan</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-auto">{{ $summary['in_progress'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Selesai</p>
                    <p class="text-2xl font-bold text-green-600 mt-auto">{{ $summary['completed'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Terlambat</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto">{{ $summary['overdue'] }}</p>
                </div>
            </div>

            <x-filter-bar :action="route('outlet.maintenance.index')" :search-value="$search" search-placeholder="Cari judul / kode / vendor / PIC" :reset-url="route('outlet.maintenance.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
                <x-filter-pills name="type" label="Tipe" :options="$typeLabels" :selected="$selectedType" all-label="Semua Tipe" />
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($jobs as $job)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('outlet.maintenance.show', $job) }}'">
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
                                        @if ($job->scheduled_time)
                                            <span class="text-gray-400">{{ \Illuminate\Support\Str::of($job->scheduled_time)->substr(0, 5) }}</span>
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
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        Belum ada pekerjaan pemeliharaan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $jobs->links() }}</div>
        </div>
    </div>
</x-app-layout>
