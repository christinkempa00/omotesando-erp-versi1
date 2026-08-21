<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.records.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manajemen Stok
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('ga.uniforms.stocks.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    + Varian Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Stok Tersedia</p>
                    <p class="text-2xl font-bold text-gray-800 mt-auto pt-1">{{ $summary['total_available'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Belum Dikembalikan</p>
                    <p class="text-2xl font-bold text-blue-600 mt-auto pt-1">{{ $summary['pending_return'] }}</p>
                    <a href="{{ route('ga.uniforms.records.index', ['status' => \App\Models\GA\UniformRecord::STATUS_ISSUED]) }}" class="text-xs text-indigo-600 hover:underline mt-1">
                        Lihat di Inventaris Seragam &rarr;
                    </a>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Stok Tidak Layak</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto pt-1">{{ $summary['total_unusable'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('ga.uniforms.stocks.index')" search-name="stock_search" :search-value="$stockSearch" search-placeholder="Cari tipe/kode/warna..." :reset-url="route('ga.uniforms.stocks.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filter-pills name="status" label="Kondisi" :options="$statusLabels" :selected="$selectedStatus" />
            </x-filter-bar>

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="text-xs text-gray-400">Per kombinasi outlet, jenis seragam, dan warna.</p>
                </div>

                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse ($stockGroups as $group)
                        <div class="border rounded-lg p-3 cursor-pointer hover:shadow-sm transition {{ $group->is_low ? 'border-red-300 bg-red-50 hover:border-red-400' : 'border-gray-200 hover:border-gray-300' }}"
                             onclick="window.location='{{ route('ga.uniforms.stocks.show-group', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}'">
                            <div class="flex items-start justify-between mb-1">
                                <div class="flex items-start gap-2 min-w-0">
                                    @if ($group->photo_path)
                                        <img src="{{ Storage::url($group->photo_path) }}"
                                             class="w-10 h-10 rounded object-cover shrink-0 border border-gray-200">
                                    @else
                                        <div class="w-10 h-10 rounded shrink-0 border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-300">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 7a2 2 0 0 1 2-2h1.5l1-1.5h5l1 1.5H19a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                                                <circle cx="12" cy="13" r="3.5" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $group->type }}</p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $group->branch->name }}{{ $group->color ? ' / '.$group->color : '' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0" onclick="event.stopPropagation()">
                                    <a href="{{ route('ga.uniforms.stocks.create', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                       title="Tambah ukuran baru untuk grup ini"
                                       class="w-6 h-6 flex items-center justify-center rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 5v14M5 12h14" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('ga.uniforms.stocks.edit-group', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                       title="Edit grup ini (Tipe Seragam/Outlet/Warna, dll — semua ukuran sekaligus)"
                                       class="w-6 h-6 flex items-center justify-center rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5" />
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5Z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('ga.uniforms.stocks.destroy-group') }}"
                                          onsubmit="return confirm('Hapus seluruh grup {{ $group->type }} ({{ $group->branch->name }}{{ $group->color ? ' / '.$group->color : '' }})?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="uniform_type" value="{{ $group->type }}">
                                        <input type="hidden" name="branch_id" value="{{ $group->branch->id }}">
                                        <input type="hidden" name="color" value="{{ $group->color }}">
                                        <button type="submit" title="Hapus grup ini"
                                                class="w-6 h-6 flex items-center justify-center rounded-md border border-gray-200 text-gray-400 hover:bg-red-50 hover:text-red-600">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14M4 6h16M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-2 pb-2 border-b border-gray-100 text-xs">
                                <div>
                                    <p class="text-gray-400">Total Tersedia</p>
                                    <p class="font-semibold text-gray-800 text-sm">{{ $group->total_available }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Tidak Layak</p>
                                    <p class="font-semibold text-red-600 text-sm">{{ $group->total_unusable }}</p>
                                </div>
                            </div>

                            <div class="divide-y divide-gray-100">
                                @foreach ($group->items as $item)
                                    <div class="flex items-center justify-between py-1.5">
                                        <div class="flex items-baseline gap-1.5 min-w-0">
                                            <span class="text-xs font-medium text-gray-800 whitespace-nowrap">Ukuran {{ $item->size ?: '-' }}</span>
                                        </div>
                                        <span class="text-xs font-semibold shrink-0 {{ $item->isLowStock() ? 'text-orange-600' : 'text-gray-800' }}">
                                            {{ $item->available_stock }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="text-[10px] text-gray-400 text-right pt-2 mt-1 border-t border-gray-100">
                                batas low stock {{ $group->items->first()?->low_stock_threshold ?? 0 }}
                            </p>
                        </div>
                    @empty
                        <p class="col-span-full text-center text-sm text-gray-500 py-8">Belum ada varian seragam.</p>
                    @endforelse
                </div>

                <div class="px-6 pb-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                    <span>
                        Menampilkan {{ $stockGroups->firstItem() ?? 0 }}-{{ $stockGroups->lastItem() ?? 0 }}
                        dari {{ $stockGroups->total() }} grup stok
                    </span>
                    {{ $stockGroups->links() }}
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('ga.uniforms.stocks.export.xlsx', request()->query()) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Export .xlsx
                </a>
                <a href="{{ route('ga.uniforms.stocks.export.pdf', request()->query()) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Export .pdf
                </a>
            </div>

            {{-- Audit trail stok (embedded, tanpa halaman terpisah) --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Riwayat</h3>
                    <a href="{{ route('ga.uniforms.movements.index') }}" class="text-xs text-indigo-600 hover:underline">Lihat Semua &rarr;</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentMovements as $movement)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ \App\Models\GA\UniformMovement::typeBadgeColor($movement->movement_type) }}">
                                    {{ $movementTypeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                </span>
                                <span class="text-gray-700 truncate">
                                    {{ $movement->uniformStock->branch->name }} / {{ $movement->uniformStock->uniform_type }}
                                    @if ($movement->uniformStock->size) / {{ $movement->uniformStock->size }} @endif
                                    @if ($movement->uniformStock->color) / {{ $movement->uniformStock->color }} @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400 shrink-0">
                                <span class="font-medium {{ $movement->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                                <span>|</span>
                                <span class="font-mono">{{ $movement->movement_code }}</span>
                                <span>|</span>
                                <span>{{ $movement->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-500">Belum ada movement.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
