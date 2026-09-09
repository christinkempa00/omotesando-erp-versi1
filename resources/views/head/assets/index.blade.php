<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Inventaris Aset
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter --}}
            <x-filter-bar :action="route('head.assets.index')" :search-value="$search" search-placeholder="Cari nama/kode/serial number..." :reset-url="route('head.assets.index')">
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
                            <th class="px-4 py-3 text-center"></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Nama Aset</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">PIC</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-hairline">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-[#FDFBF4]">
                                <td class="px-4 py-3">
                                    @if ($asset->image_path)
                                        <img src="{{ Storage::url($asset->image_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-ink-muted text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-ink">{{ $asset->asset_code }}</td>
                                <td class="px-4 py-3 text-sm text-ink">{{ $asset->name }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $asset->branch->name }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $asset->custodian_name ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
                                        {{ $statusLabels[$asset->status] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada aset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div><x-table-footer :paginator="$assets" /></div>
        </div>
    </div>
</x-app-layout>
