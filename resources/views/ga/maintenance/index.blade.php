<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Jadwal Pemeliharaan
            </h2>
            <a href="{{ route('ga.maintenance.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gold-500 text-white text-sm font-medium rounded-md hover:bg-gold-600">
                + Pemeliharaan
            </a>
        </div>
    </x-slot>

    @php
        $jobsData = $calendarJobs->mapWithKeys(function ($job) use ($statusLabels) {
            return [$job->id => [
                'code' => $job->job_code,
                'title' => $job->title,
                'type' => \App\Models\GA\MaintenanceJob::typeLabels()[$job->type] ?? $job->type,
                'asset' => $job->asset?->name,
                'date' => $job->scheduled_date?->translatedFormat('d M Y'),
                'time' => $job->scheduled_time ? \Illuminate\Support\Str::of($job->scheduled_time)->substr(0, 5)->toString() : null,
                'priority' => \App\Models\GA\MaintenanceJob::priorityLabels()[$job->priority] ?? $job->priority,
                'priorityColor' => \App\Models\GA\MaintenanceJob::priorityBadgeColor($job->priority),
                'status' => $statusLabels[$job->status] ?? $job->status,
                'statusColor' => \App\Models\GA\MaintenanceJob::statusBadgeColor($job->status),
                'pic' => $job->pic_name,
                'vendor' => $job->vendor_name,
                'location' => $job->location,
                'notes' => $job->notes,
                'cost' => $job->cost !== null ? 'Rp '.number_format((float) $job->cost, 0, ',', '.') : null,
                'overdue' => $job->isOverdue(),
                'showUrl' => route('ga.maintenance.show', $job),
                'editUrl' => route('ga.maintenance.edit', $job),
                'deleteUrl' => route('ga.maintenance.destroy', $job),
            ]];
        });
    @endphp

    <div class="py-8"
         x-data="{
            selectedJob: null,
            pickingDate: null,
            pickingJobs: [],
            jobsData: {{ Illuminate\Support\Js::from($jobsData) }},
            onDateClick(dateStr, jobIds) {
                if (jobIds.length === 0) return;
                if (jobIds.length === 1) {
                    this.selectedJob = this.jobsData[jobIds[0]];
                    return;
                }
                this.pickingDate = dateStr;
                this.pickingJobs = jobIds.map(id => this.jobsData[id]);
            },
            pickJob(job) {
                this.pickingDate = null;
                this.selectedJob = job;
            }
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

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

            {{-- Preview tiket pemeliharaan: muncul di halaman ini saat kartu kalender diklik --}}
            <div x-show="selectedJob" x-cloak style="display:none;"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="selectedJob = null"></div>

                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden" x-show="selectedJob">
                    <template x-if="selectedJob">
                        <div>
                            <div class="px-5 py-4" :class="selectedJob.priorityColor">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-xs font-mono opacity-70" x-text="selectedJob.code"></p>
                                        <p class="text-base font-bold mt-0.5" x-text="selectedJob.title"></p>
                                    </div>
                                    <button type="button" @click="selectedJob = null" class="opacity-60 hover:opacity-100">
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="border-t-2 border-dashed border-gray-200 relative">
                                <div class="absolute -left-3 -top-3 w-6 h-6 bg-gray-100 rounded-full"></div>
                                <div class="absolute -right-3 -top-3 w-6 h-6 bg-gray-100 rounded-full"></div>
                            </div>

                            <div class="p-5 space-y-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="selectedJob.statusColor" x-text="selectedJob.status"></span>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="selectedJob.priorityColor" x-text="selectedJob.priority"></span>
                                    <span x-show="selectedJob.overdue" class="text-xs font-medium text-red-600">Terlambat</span>
                                </div>

                                <dl class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt class="text-gray-400 text-xs">Tipe</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.type"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">Aset</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.asset || '-'"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">Tanggal</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.date"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">Jam</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.time || '-'"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">PIC</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.pic || '-'"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">Vendor</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.vendor || '-'"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">Lokasi</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.location || '-'"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-gray-400 text-xs">Estimasi Biaya</dt>
                                        <dd class="font-medium text-gray-800" x-text="selectedJob.cost || '-'"></dd>
                                    </div>
                                </dl>

                                <div x-show="selectedJob.notes">
                                    <dt class="text-gray-400 text-xs">Catatan</dt>
                                    <dd class="text-sm text-gray-700 whitespace-pre-line" x-text="selectedJob.notes"></dd>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <a :href="selectedJob.editUrl" class="text-sm text-gray-500 hover:text-gray-700">Edit</a>
                                        <form :action="selectedJob.deleteUrl" method="POST"
                                              @submit="if (! confirm('Hapus pekerjaan ini?')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">Hapus</button>
                                        </form>
                                    </div>
                                    <a :href="selectedJob.showUrl" class="text-sm text-gold-600 hover:text-gold-800 font-medium">Buka Detail Lengkap &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Preview daftar jadwal: muncul saat tanggal dgn >1 jadwal diklik di kalender mini --}}
            <div x-show="pickingDate" x-cloak style="display:none;"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50" @click="pickingDate = null"></div>

                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-800" x-text="pickingDate"></h3>
                        <button type="button" @click="pickingDate = null" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>
                    <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="job in pickingJobs" :key="job.code">
                            <button type="button" @click="pickJob(job)" class="w-full text-left px-5 py-3 hover:bg-gray-50">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="job.title"></p>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium shrink-0" :class="job.statusColor" x-text="job.status"></span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5" x-text="(job.asset || '-') + (job.time ? ' · ' + job.time : '')"></p>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Kalender mini + jadwal mendatang — sengaja dijaga ringkas, tanpa
                 kalender minggu bergrid jam supaya tidak over-design. --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @include('ga.maintenance._mini-calendar')

                <div class="bg-white shadow-sm rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Jadwal Mendatang</h3>
                    <div class="space-y-3">
                        @forelse ($upcomingJobs as $job)
                            <a href="{{ route('ga.maintenance.show', $job) }}" class="flex items-start gap-3 group">
                                <div class="w-9 h-9 rounded-full bg-gold-50 flex items-center justify-center text-gold-600 text-xs font-semibold shrink-0">
                                    {{ optional($job->scheduled_date)->format('d') }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate group-hover:text-gold-600">{{ $job->title }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $job->scheduled_date?->translatedFormat('d M') }}
                                        @if ($job->scheduled_time)
                                            · {{ \Illuminate\Support\Str::of($job->scheduled_time)->substr(0, 5) }}
                                        @endif
                                    </p>
                                </div>
                            </a>
                        @empty
                            <p class="text-sm text-gray-400">Tidak ada jadwal mendatang.</p>
                        @endforelse
                    </div>
                    <a href="#daftar-pemeliharaan" class="mt-4 inline-block text-sm text-gold-600 hover:text-gold-800">
                        Lihat Semua &rarr;
                    </a>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('ga.maintenance.index')" :search-value="$search" search-placeholder="Cari judul / kode / vendor / PIC" :reset-url="route('ga.maintenance.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
                <x-filter-pills name="type" label="Tipe" :options="$typeLabels" :selected="$selectedType" all-label="Semua Tipe" />
            </x-filter-bar>

            {{-- Tabel --}}
            <div id="daftar-pemeliharaan" class="bg-white shadow-sm rounded-lg overflow-hidden">
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
                            @forelse ($jobs as $job)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ga.maintenance.show', $job) }}'">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $job->job_code }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800">{{ $job->title }}</div>
                                        <div class="text-xs text-gray-500">{{ \App\Models\GA\MaintenanceJob::typeLabels()[$job->type] ?? $job->type }}</div>
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
                                    <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-end gap-3">
                                            <form method="POST" action="{{ route('ga.maintenance.destroy', $job) }}"
                                                  onsubmit="return confirm('Hapus pekerjaan {{ $job->job_code }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus" class="text-red-600 hover:text-red-800">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                        <path d="M10 11v6M14 11v6"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
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
