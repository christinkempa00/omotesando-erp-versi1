<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Stok Seragam Outlet {{ auth()->user()->branch?->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-stretch">
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Stok Tersedia</p>
                    <p class="text-2xl font-bold text-gray-800 mt-auto pt-1">{{ $summary['total_available'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Belum Dikembalikan</p>
                    <p class="text-2xl font-bold text-blue-600 mt-auto pt-1">{{ $summary['pending_return'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Stok Tidak Layak</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto pt-1">{{ $summary['total_unusable'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('outlet.uniforms.stocks.index')" search-name="stock_search" :search-value="$stockSearch" search-placeholder="Cari tipe/kode/warna..." :reset-url="route('outlet.uniforms.stocks.index')">
                <x-filter-pills name="status" label="Kondisi" :options="$statusLabels" :selected="$selectedStatus" />
            </x-filter-bar>

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="text-xs text-gray-400">Per kombinasi jenis seragam dan warna. Lihat saja, perubahan stok dikelola GA.</p>
                </div>

                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse ($stockGroups as $group)
                        <div class="border rounded-lg p-3 {{ $group->is_low ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
                            <div class="flex items-start gap-2 min-w-0 mb-1">
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
                                    <p class="text-xs text-gray-400 truncate">{{ $group->color ?: '-' }}</p>
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
                                        <span class="text-xs font-medium text-gray-800 whitespace-nowrap">Ukuran {{ $item->size ?: '-' }}</span>
                                        <span class="text-xs font-semibold shrink-0 {{ $item->isLowStock() ? 'text-orange-600' : 'text-gray-800' }}">
                                            {{ $item->available_stock }}
                                        </span>
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

        </div>
    </div>
</x-app-layout>
