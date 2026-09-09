<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-ink leading-tight">
                Inventaris Seragam
            </h2>
            <a href="{{ route('ga.uniforms.records.create') }}"
               class="inline-flex items-center btn-primary">
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
                    <label class="block text-xs font-medium text-ink-muted mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-hairline text-sm shadow-sm focus:border-action focus:ring-action">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-hairline">
                    <thead class="border-b border-hairline">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Karyawan</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Seragam</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tgl Serah</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-hairline">
                        @forelse ($records as $record)
                            <tr class="hover:bg-[#FDFBF4] cursor-pointer" onclick="window.location='{{ route('ga.uniforms.records.show', $record) }}'">
                                <td class="px-4 py-3 text-sm font-mono text-ink-muted">{{ $record->record_code }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-ink">{{ $record->employee_name }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->summaryLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->outletLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $record->issue_date->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\UniformRecord::statusBadgeColor($record->status) }}">
                                        {{ $statusLabels[$record->status] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada catatan serah-terima.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <x-table-footer :paginator="$records">
                <x-slot name="export">
                    <div class="relative" x-data="{ exportOpen: false }">
                        <button type="button" @click="exportOpen = !exportOpen"
                                class="btn-secondary inline-flex items-center gap-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/>
                            </svg>
                            Export
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                        <div x-show="exportOpen" @click.outside="exportOpen = false" x-cloak
                             class="absolute right-0 mt-2 w-48 bg-white border border-hairline rounded-lg shadow-lg z-20 overflow-hidden"
                             style="display:none;">
                            <a href="{{ route('ga.uniforms.records.export.xlsx', request()->query()) }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink hover:bg-surface">
                                <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/><path d="m9 12 6 6M15 12l-6 6"/></svg>
                                Excel (.xlsx)
                            </a>
                            <a href="{{ route('ga.uniforms.records.export.pdf', request()->query()) }}"
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink hover:bg-surface border-t border-hairline">
                                <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                PDF (.pdf)
                            </a>
                        </div>
                    </div>
                </x-slot>
            </x-table-footer>
        </div>
    </div>
</x-app-layout>
