<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Work Log
            </h2>
            <a href="{{ route('ga.worklogs.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Buat Work Log
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter --}}
            <x-filter-bar :action="route('ga.worklogs.index')" :search-value="$search" search-placeholder="Nama teknisi..." :reset-url="route('ga.worklogs.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-filter-pills name="category" label="Kategori Pengerjaan" :options="$categoryLabels" :selected="$selectedCategory" all-label="Semua Kategori" />

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Tanggal Pengerjaan</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_from" value="{{ $dateFrom }}" placeholder="Dari"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="date" name="date_to" value="{{ $dateTo }}" placeholder="Sampai"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700">Distribusi Pekerjaan per Teknisi</h3>
                <p class="text-xs text-gray-400 mb-4">Berdasarkan filter yang diterapkan di atas.</p>
                <x-donut-chart :data="$technicianByCount" :labels="$technicianLabels" :total="$technicianTotal" size="w-28 h-28" />
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal &amp; Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teknisi in Charge</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teknisi Assist</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($workLogs as $workLog)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ga.worklogs.show', $workLog) }}'">
                                <td class="px-4 py-3">
                                    @if ($workLog->attachments->isNotEmpty())
                                        <img src="{{ Storage::url($workLog->attachments->first()->photo_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="text-gray-900">{{ optional($workLog->work_date)->format('d/m/y') }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ substr($workLog->start_time, 0, 5) }}@if ($workLog->end_time)&ndash;{{ substr($workLog->end_time, 0, 5) }}@endif
                                    </div>
                                    @if ($workLog->durationLabel())
                                        <div class="text-xs text-gray-400">{{ $workLog->durationLabel() }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $categoryLabels[$workLog->category] ?? $workLog->category }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $workLog->branch->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $workLog->technician_in_charge }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $workLog->technician_assist ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($workLog->isComplete())
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Selesai</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Belum Ada Hasil</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm" onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('ga.worklogs.edit', $workLog) }}" title="Edit" class="text-indigo-600 hover:text-indigo-900">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1h15a1 1 0 0 0 1-1v-7"/>
                                                <path d="M18.5 2.5a1.71 1.71 0 0 1 2 2L12 13l-4 1 1-4Z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('ga.worklogs.destroy', $workLog) }}" class="inline"
                                              onsubmit="return confirm('Hapus work log ini?')">
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
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada Work Log.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $workLogs->links() }}</div>
        </div>
    </div>
</x-app-layout>
