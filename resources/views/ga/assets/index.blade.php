<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-ink leading-tight">
                Inventaris Aset
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('ga.assets.create') }}"
                   class="inline-flex items-center btn-gold">
                    Buat Aset
                </a>
            </div>
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
            <div class="flex items-center gap-2">
                <div class="flex-1 min-w-0">
                    <x-filter-bar :action="route('ga.assets.index')" :search-value="$search" search-placeholder="Nama/kode/serial number..." :reset-url="route('ga.assets.index')">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                            <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500">
                                <option value="">Semua Outlet</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />

                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Tanggal Beli</p>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="date_from" value="{{ $dateFrom }}" placeholder="Dari"
                                       class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500">
                                <input type="date" name="date_to" value="{{ $dateTo }}" placeholder="Sampai"
                                       class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500">
                            </div>
                        </div>
                    </x-filter-bar>
                </div>

                <button type="button" id="qr-select-toggle" class="shrink-0 inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Pilih Aset
                </button>
            </div>

            {{-- Catatan: sengaja BUKAN <form>, supaya tidak nested dengan <form> hapus
                 per-baris di dalam tabel (nested <form> invalid di HTML dan akan merusak
                 submit). Checkbox terpilih dikumpulkan lalu di-navigate via JS. --}}
            <div class="glass-panel overflow-hidden">
                <div id="qr-select-bar" style="display:none" class="items-center justify-between px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                    <span id="qr-select-count" class="text-xs text-gray-500">Belum ada aset dipilih</span>
                    <button type="button" id="qr-select-submit" disabled
                            class="btn-gold-sm disabled:opacity-40 disabled:cursor-not-allowed">
                        Unduh QR Terpilih
                    </button>
                </div>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="qr-select-col px-4 py-3" style="display:none">
                                <input type="checkbox" id="qr-select-all" class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                            </th>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Aset</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Aset &amp; Merk</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet &amp; Lokasi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Beli</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penanggung Jawab</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ga.assets.show', $asset) }}'">
                                <td class="qr-select-col px-4 py-3" style="display:none" onclick="event.stopPropagation()">
                                    <input type="checkbox" value="{{ $asset->id }}" class="qr-select-row rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                                </td>
                                <td class="px-4 py-3">
                                    @if ($asset->image_path)
                                        <img src="{{ Storage::url($asset->image_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $asset->asset_code }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="text-gray-900">{{ $asset->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $asset->brand ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="text-gray-900">{{ $asset->outletLabel() }}</div>
                                    <div class="text-xs text-gray-500">{{ $asset->location ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ optional($asset->purchase_date)->format('d/m/y') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
                                        {{ $statusLabels[$asset->status] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $asset->custodian_name ?: '-' }}</td>
                                <td class="px-4 py-3 text-right text-sm" onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('ga.assets.qr', $asset) }}" title="QR" class="text-gray-500 hover:text-gray-800">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                                                <rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 20h.01"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('ga.assets.edit', $asset) }}" title="Edit" class="text-gold-600 hover:text-gold-700">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1h15a1 1 0 0 0 1-1v-7"/>
                                                <path d="M18.5 2.5a1.71 1.71 0 0 1 2 2L12 13l-4 1 1-4Z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('ga.assets.destroy', $asset) }}" class="inline"
                                              onsubmit="return confirm('Hapus aset {{ $asset->asset_code }}?')">
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
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada aset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>{{ $assets->links() }}</div>
                <div class="flex gap-2">
                    <a href="{{ route('ga.assets.export.xlsx', request()->query()) }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                        Export .xlsx
                    </a>
                    <a href="{{ route('ga.assets.export.pdf', request()->query()) }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                        Export .pdf
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const rowCheckboxes = Array.from(document.querySelectorAll('.qr-select-row'));
            const selectAll = document.getElementById('qr-select-all');
            const countLabel = document.getElementById('qr-select-count');
            const submitBtn = document.getElementById('qr-select-submit');
            const selectBar = document.getElementById('qr-select-bar');
            const selectCols = Array.from(document.querySelectorAll('.qr-select-col'));
            const toggleBtn = document.getElementById('qr-select-toggle');
            const bulkQrUrl = {!! json_encode(route('ga.assets.qr-labels')) !!};

            let selectMode = false;

            function setSelectMode(on) {
                selectMode = on;
                selectBar.style.display = on ? 'flex' : 'none';
                selectCols.forEach(el => { el.style.display = on ? 'table-cell' : 'none'; });
                toggleBtn.textContent = on ? 'Batal Pilih' : 'Pilih Aset';
                if (!on) {
                    rowCheckboxes.forEach(cb => { cb.checked = false; });
                    updateState();
                }
            }

            toggleBtn.addEventListener('click', () => setSelectMode(!selectMode));

            function updateState() {
                const checked = rowCheckboxes.filter(cb => cb.checked);
                countLabel.textContent = checked.length > 0
                    ? checked.length + ' aset dipilih'
                    : 'Belum ada aset dipilih';
                submitBtn.disabled = checked.length === 0;
                selectAll.checked = checked.length > 0 && checked.length === rowCheckboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < rowCheckboxes.length;
            }

            selectAll.addEventListener('change', () => {
                rowCheckboxes.forEach(cb => { cb.checked = selectAll.checked; });
                updateState();
            });

            rowCheckboxes.forEach(cb => cb.addEventListener('change', updateState));

            submitBtn.addEventListener('click', () => {
                const ids = rowCheckboxes.filter(cb => cb.checked).map(cb => cb.value);
                if (ids.length === 0) return;
                const params = ids.map(id => 'ids[]=' + encodeURIComponent(id)).join('&');
                window.location = bulkQrUrl + '?' + params;
            });

            updateState();
        })();
    </script>
</x-app-layout>
