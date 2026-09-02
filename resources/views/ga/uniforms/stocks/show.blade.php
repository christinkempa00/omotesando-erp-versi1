<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.stocks.index')" />
                <h2 class="font-semibold text-xl text-ink leading-tight">
                    {{ $stock->uniform_type }}
                    <span class="ml-2 font-mono text-sm text-gray-400">{{ $stock->stock_code }}</span>
                </h2>
            </div>
            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('ga.uniforms.stocks.edit', $stock) }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">Edit</a>
                    <form method="POST" action="{{ route('ga.uniforms.stocks.destroy', $stock) }}"
                          onsubmit="return confirm('Hapus varian ini? Semua riwayat movement ikut terhapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100">Hapus</button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Detail --}}
            <div class="glass-panel p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="sm:col-span-1">
                    @if ($stock->stock_photo_path)
                        <img src="{{ Storage::url($stock->stock_photo_path) }}" class="w-full rounded-lg object-cover">
                    @else
                        <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                            Belum ada foto
                        </div>
                    @endif
                    @if ($stock->isLowStock())
                        <span class="mt-3 inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-700">
                            Low Stock
                        </span>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Ukuran</dt><dd class="font-medium text-gray-900">{{ $stock->size ?: '-' }}</dd></div>
                        <div><dt class="text-gray-500">Warna</dt><dd class="font-medium text-gray-900">{{ $stock->color ?: '-' }}</dd></div>
                        <div><dt class="text-gray-500">Outlet</dt><dd class="font-medium text-gray-900">{{ $stock->outletLabel() }}</dd></div>
                        <div><dt class="text-gray-500">Ambang Low Stock</dt><dd class="font-medium text-gray-900">{{ $stock->low_stock_threshold }}</dd></div>
                        <div><dt class="text-gray-500">Stok Tersedia</dt><dd class="font-bold text-green-600 text-lg">{{ $stock->available_stock }}</dd></div>
                        <div><dt class="text-gray-500">Stok Rusak</dt><dd class="font-bold text-red-600 text-lg">{{ $stock->unusable_stock }}</dd></div>
                    </dl>
                </div>
            </div>

            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                <div class="max-w-sm">
                    <div class="glass-panel p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Restock (+)</h3>
                        <form method="POST" action="{{ route('ga.uniforms.stocks.restock', $stock) }}" class="space-y-2">
                            @csrf
                            <input type="number" name="quantity" min="1" required placeholder="Jumlah"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-gold-500 focus:ring-gold-500">
                            <input type="text" name="notes" placeholder="Catatan (opsional)"
                                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-gold-500 focus:ring-gold-500">
                            <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                Tambah Stok
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Riwayat movement --}}
            <div class="glass-panel overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Riwayat Movement</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tipe</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Tersedia Sesudah</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Rusak Sesudah</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Catatan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($movements as $movement)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $movement->movement_code }}</td>
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada movement.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $movements->links() }}</div>
            </div>

            <div>
                <a href="{{ route('ga.uniforms.stocks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
