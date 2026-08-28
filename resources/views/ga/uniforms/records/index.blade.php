<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Inventaris Seragam
            </h2>
            <a href="{{ route('ga.uniforms.records.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Buat Serah Terima
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
            <x-filter-bar :action="route('ga.uniforms.records.index')" :search-value="$search" search-placeholder="Cari nama karyawan/kode/tipe..." :reset-url="route('ga.uniforms.records.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Karyawan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seragam</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Serah</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($records as $record)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ga.uniforms.records.show', $record) }}'">
                                <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $record->record_code }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $record->employee_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->summaryLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->outletLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->issue_date->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\UniformRecord::statusBadgeColor($record->status) }}">
                                        {{ $statusLabels[$record->status] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada catatan serah-terima.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>{{ $records->links() }}</div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('ga.uniforms.records.export.xlsx', request()->query()) }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                        Export .xlsx
                    </a>
                    <a href="{{ route('ga.uniforms.records.export.pdf', request()->query()) }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                        Export .pdf
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
