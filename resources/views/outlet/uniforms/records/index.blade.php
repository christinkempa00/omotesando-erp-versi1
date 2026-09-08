<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-ink leading-tight">
                Serah Terima Seragam Outlet {{ auth()->user()->branch?->name }}
            </h2>
            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_RECORDS))
                <a href="{{ route('outlet.uniforms.records.create') }}"
                   class="inline-flex items-center btn-primary">
                    Buat Serah Terima
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            <x-filter-bar :action="route('outlet.uniforms.records.index')" :search-value="$search" search-placeholder="Cari nama karyawan/kode/tipe..." :reset-url="route('outlet.uniforms.records.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-hairline">
                    <thead class="border-b border-hairline">
                        <tr>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Karyawan</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Seragam</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tgl Serah</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-hairline">
                        @forelse ($records as $record)
                            <tr class="hover:bg-[#FDFBF4] cursor-pointer" onclick="window.location='{{ route('outlet.uniforms.records.show', $record) }}'">
                                <td class="px-4 py-3 text-sm font-mono text-ink-muted">{{ $record->record_code }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-ink">{{ $record->employee_name }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->summaryLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->issue_date->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\UniformRecord::statusBadgeColor($record->status) }}">
                                        {{ $statusLabels[$record->status] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada catatan serah-terima.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div><x-table-footer :paginator="$records" /></div>
        </div>
    </div>
</x-app-layout>
