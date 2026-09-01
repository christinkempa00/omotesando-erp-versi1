<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.outlet-areas.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Area Pemeriksaan Outlet {{ $branch->name }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-5">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ $editingArea ? 'Edit Area' : 'Tambah Area Baru' }}</h3>
                @include('ga.outlet-areas._form', ['area' => $editingArea, 'branch' => $branch])
            </div>

            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-800">Daftar Area</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-8 px-3 py-3"></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Nama</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="area-sortable-list">
                        @forelse ($areas as $area)
                            <tr class="hover:bg-gray-50" data-area-id="{{ $area->id }}">
                                <td class="px-3 py-3 text-gray-300 drag-handle cursor-move" title="Seret untuk urutkan">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="9" cy="6" r="1.4" /><circle cx="9" cy="12" r="1.4" /><circle cx="9" cy="18" r="1.4" />
                                        <circle cx="15" cy="6" r="1.4" /><circle cx="15" cy="12" r="1.4" /><circle cx="15" cy="18" r="1.4" />
                                    </svg>
                                </td>
                                <td class="px-4 py-3 text-gray-800">{{ $area->name }}</td>
                                <td class="px-4 py-3">
                                    @if ($area->is_active)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('ga.outlet-areas.manage', ['branch' => $branch, 'edit' => $area->id]) }}" class="text-xs font-medium text-accent hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('ga.outlet-areas.toggle', [$branch, $area]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-gray-500 hover:text-gray-700">
                                                {{ $area->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('ga.outlet-areas.destroy', [$branch, $area]) }}" class="inline"
                                              onsubmit="return confirm('Hapus area {{ $area->name }} permanen? Aksi ini tidak bisa dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada area pemeriksaan untuk outlet ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($areas->count() > 1)
                    <p class="px-6 py-3 text-xs text-gray-400 border-t border-gray-100">Seret ikon di kiri baris untuk mengubah urutan.</p>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.getElementById('area-sortable-list');
            if (! list) return;

            Sortable.create(list, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: () => {
                    const token = document.querySelector('meta[name="csrf-token"]').content;

                    Array.from(list.children).forEach((row, index) => {
                        const areaId = row.dataset.areaId;
                        if (! areaId) return;

                        fetch(`{{ route('ga.outlet-areas.manage', $branch) }}/${areaId}/reorder`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({ sort_order: index }),
                        }).catch(() => {});
                    });
                },
            });
        });
    </script>
</x-app-layout>
