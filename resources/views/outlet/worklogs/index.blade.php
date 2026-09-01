<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Work Log Outlet {{ auth()->user()->branch?->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-filter-bar :action="route('outlet.worklogs.index')" :search-value="$search" search-placeholder="Nama teknisi..." :reset-url="route('outlet.worklogs.index')">
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

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal &amp; Waktu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teknisi in Charge</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teknisi Assist</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($workLogs as $workLog)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('outlet.worklogs.show', $workLog) }}'">
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
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $workLog->technician_in_charge }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $workLog->technician_assist ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($workLog->isComplete())
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Selesai</span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Belum Ada Hasil</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada Work Log.</td>
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
