<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Riwayat Movement Seragam
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter --}}
            <x-filter-bar :action="route('ga.uniforms.movements.index')" :search-value="$search" search-placeholder="Cari kode movement/tipe..." :reset-url="route('ga.uniforms.movements.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Varian</label>
                    <select name="uniform_stock_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Varian</option>
                        @foreach ($stocks as $stock)
                            <option value="{{ $stock->id }}" @selected($selectedStock == $stock->id)>{{ $stock->stock_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filter-pills name="type" label="Tipe" :options="$typeLabels" :selected="$selectedType" all-label="Semua Tipe" />
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Kode</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Varian</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Outlet</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tipe</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Tersedia Sesudah</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500">Rusak Sesudah</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Catatan</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Oleh</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($movements as $movement)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $movement->movement_code }}</td>
                                <td class="px-4 py-3 text-gray-800">
                                    <a href="{{ route('ga.uniforms.stocks.show', $movement->uniformStock) }}" class="text-indigo-600 hover:underline">
                                        {{ $movement->uniformStock->stock_code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $movement->uniformStock->branch->name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\UniformMovement::typeBadgeColor($movement->movement_type) }}">
                                        {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium {{ $movement->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ $movement->quantity }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $movement->available_after }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $movement->unusable_after }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $movement->notes ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $movement->createdBy?->name ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $movement->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada movement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $movements->links() }}</div>
        </div>
    </div>
</x-app-layout>
