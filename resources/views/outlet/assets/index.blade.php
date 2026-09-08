<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Aset Outlet {{ auth()->user()->branch?->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-filter-bar :action="route('outlet.assets.index')" :search-value="$search" search-placeholder="Cari nama/kode/serial number..." :reset-url="route('outlet.assets.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-hairline">
                    <thead class="border-b border-hairline">
                        <tr>
                            <th class="px-4 py-3 text-center"></th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Nama Aset</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">PIC</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-hairline">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-[#FDFBF4] cursor-pointer" onclick="window.location='{{ route('outlet.assets.show', $asset) }}'">
                                <td class="px-4 py-3">
                                    @if ($asset->image_path)
                                        <img src="{{ Storage::url($asset->image_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-ink-muted text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-ink">{{ $asset->asset_code }}</td>
                                <td class="px-4 py-3 text-sm text-ink">{{ $asset->name }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $asset->custodian_name ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
                                        {{ $statusLabels[$asset->status] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada aset.</td>
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
