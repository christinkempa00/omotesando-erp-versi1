<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.records.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Stock Management
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('ga.uniforms.records.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Serah-terima
                </a>
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
                        Lihat di Uniform Inventory &rarr;
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
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex items-start justify-between mb-1">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $group->type }}</p>
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ $group->branch->name }}{{ $group->color ? ' / '.$group->color : '' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    @if ($group->is_low)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-700">
                                            Menipis
                                        </span>
                                    @endif
                                    <a href="{{ route('ga.uniforms.stocks.create', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                       title="Tambah ukuran baru untuk grup ini"
                                       class="w-6 h-6 flex items-center justify-center rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 5v14M5 12h14" />
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
                                            <span class="text-xs font-medium text-gray-800 whitespace-nowrap">Size {{ $item->size ?: '-' }}</span>
                                            <span class="text-[10px] text-gray-400 whitespace-nowrap">batas {{ $item->low_stock_threshold }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="text-xs font-semibold {{ $item->isLowStock() ? 'text-orange-600' : 'text-gray-800' }}">
                                                {{ $item->available_stock }}
                                            </span>
                                            <form method="POST" action="{{ route('ga.uniforms.stocks.destroy', $item) }}"
                                                  onsubmit="return confirm('Hapus varian ukuran {{ $item->size ?: '-' }} ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-gray-400 hover:text-red-600">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14M4 6h16M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
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

            {{-- Audit trail stok (embedded, tanpa halaman terpisah) --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">History</h3>
                    <a href="{{ route('ga.uniforms.movements.index') }}" class="text-xs text-indigo-600 hover:underline">Lihat semua &rarr;</a>
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
