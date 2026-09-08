<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.uniforms.stocks.index')" parent="Manajemen Stok" current="Detail Stok" />
            <h2 class="font-semibold text-xl text-ink leading-tight">
                {{ $stock->uniform_type }}
                <span class="ml-2 font-mono text-sm text-ink-muted">{{ $stock->stock_code }}</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="card-flat p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="sm:col-span-1">
                    @if ($stock->stock_photo_path)
                        <img src="{{ Storage::url($stock->stock_photo_path) }}" class="w-full rounded-lg object-cover">
                    @else
                        <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-ink-muted text-sm">
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
                        <div><dt class="text-ink-muted">Ukuran</dt><dd class="font-medium text-ink">{{ $stock->size ?: '-' }}</dd></div>
                        <div><dt class="text-ink-muted">Warna</dt><dd class="font-medium text-ink">{{ $stock->color ?: '-' }}</dd></div>
                        <div><dt class="text-ink-muted">Stok Tersedia</dt><dd class="font-bold text-green-600 text-lg">{{ $stock->available_stock }}</dd></div>
                        <div><dt class="text-ink-muted">Stok Rusak</dt><dd class="font-bold text-red-600 text-lg">{{ $stock->unusable_stock }}</dd></div>
                    </dl>
                </div>
            </div>

            <div>
                <a href="{{ route('outlet.uniforms.stocks.index') }}" class="text-sm text-ink-muted hover:text-ink">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
