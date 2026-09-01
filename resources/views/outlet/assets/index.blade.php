<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Aset Outlet {{ auth()->user()->branch?->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-filter-bar :action="route('outlet.assets.index')" :search-value="$search" search-placeholder="Cari nama/kode/serial number..." :reset-url="route('outlet.assets.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Aset</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PIC</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('outlet.assets.show', $asset) }}'">
                                <td class="px-4 py-3">
                                    @if ($asset->image_path)
                                        <img src="{{ Storage::url($asset->image_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $asset->asset_code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $asset->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $asset->custodian_name ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
                                        {{ $statusLabels[$asset->status] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada aset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $assets->links() }}</div>
        </div>
    </div>
</x-app-layout>
