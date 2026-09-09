<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Inventaris Seragam
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Kartu ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Total Varian</p>
                    <p class="text-2xl font-bold text-ink mt-auto">{{ $summary['total_variants'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Stok Tersedia</p>
                    <p class="text-2xl font-bold text-green-600 mt-auto">{{ $summary['total_available'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Stok Rusak</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto">{{ $summary['total_unusable'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-sm text-ink-muted">Varian Low Stock</p>
                    <p class="text-2xl font-bold text-orange-600 mt-auto">{{ $summary['low_stock_count'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('head.uniforms.index')" :search-value="$search" search-placeholder="Cari tipe/kode/warna..." :reset-url="route('head.uniforms.index')">
                <div>
                    <label class="block text-xs font-medium text-ink-muted mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-hairline text-sm shadow-sm focus:border-action focus:ring-action">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-ink-muted">
                    <input type="checkbox" name="low_stock" value="1" @checked($lowStockOnly)
                           class="rounded border-hairline text-action focus:ring-action">
                    Low stock saja
                </label>
            </x-filter-bar>

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-hairline">
                    <thead class="border-b border-hairline">
                        <tr>
                            <th class="px-4 py-3 text-center"></th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tipe</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Ukuran</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Warna</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Outlet</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tersedia</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Rusak</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-hairline">
                        @forelse ($stocks as $stock)
                            <tr class="hover:bg-[#FDFBF4]">
                                <td class="px-4 py-3">
                                    @if ($stock->stock_photo_path)
                                        <img src="{{ Storage::url($stock->stock_photo_path) }}" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-ink-muted text-xs">N/A</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-ink">{{ $stock->stock_code }}</td>
                                <td class="px-4 py-3 text-sm text-ink">{{ $stock->uniform_type }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $stock->size ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $stock->color ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-ink-muted">{{ $stock->branch->name }}</td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <span class="font-semibold {{ $stock->isLowStock() ? 'text-orange-600' : 'text-ink' }}">
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
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada varian seragam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div><x-table-footer :paginator="$stocks" /></div>
        </div>
    </div>
</x-app-layout>
