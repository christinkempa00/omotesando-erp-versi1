<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Inventaris Seragam
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Total Varian</p>
                    <p class="text-2xl font-bold text-gray-800 mt-auto">{{ $summary['total_variants'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Stok Tersedia</p>
                    <p class="text-2xl font-bold text-green-600 mt-auto">{{ $summary['total_available'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Stok Rusak</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto">{{ $summary['total_unusable'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4 flex flex-col">
                    <p class="text-sm text-gray-500">Varian Low Stock</p>
                    <p class="text-2xl font-bold text-orange-600 mt-auto">{{ $summary['low_stock_count'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('head.uniforms.index')" :search-value="$search" search-placeholder="Cari tipe/kode/warna..." :reset-url="route('head.uniforms.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="low_stock" value="1" @checked($lowStockOnly)
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Low stock saja
                </label>
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ukuran</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warna</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tersedia</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rusak</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($stocks as $stock)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    @if ($stock->stock_photo_path)
                                        <img src="{{ Storage::url($stock->stock_photo_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $stock->stock_code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $stock->uniform_type }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $stock->size ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $stock->color ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $stock->branch->name }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <span class="font-semibold {{ $stock->isLowStock() ? 'text-orange-600' : 'text-gray-800' }}">
                                        {{ $stock->available_stock }}
                                    </span>
                                    @if ($stock->isLowStock())
                                        <span class="ml-1 text-xs text-orange-500">(low)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">{{ $stock->unusable_stock }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada varian seragam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $stocks->links() }}</div>
        </div>
    </div>
</x-app-layout>
