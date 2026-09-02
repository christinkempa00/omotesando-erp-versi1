<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.stocks.index')" />
                <h2 class="font-semibold text-xl text-ink leading-tight">
                    {{ $first->uniform_type }}
                    <span class="ml-2 text-sm text-gray-400 font-normal">{{ $first->outletLabel() }}{{ $first->color ? ' / '.$first->color : '' }}</span>
                </h2>
            </div>
            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('ga.uniforms.stocks.edit-group', ['uniform_type' => $first->uniform_type, 'branch_id' => $first->branch_id, 'color' => $first->color]) }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">Edit Grup</a>
                    <form method="POST" action="{{ route('ga.uniforms.stocks.destroy-group') }}"
                          onsubmit="return confirm('Hapus seluruh grup {{ $first->uniform_type }} ({{ $first->branch->name }}{{ $first->color ? ' / '.$first->color : '' }})? Semua ukuran &amp; riwayat movement-nya ikut terhapus.');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="uniform_type" value="{{ $first->uniform_type }}">
                        <input type="hidden" name="branch_id" value="{{ $first->branch_id }}">
                        <input type="hidden" name="color" value="{{ $first->color }}">
                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100">Hapus Grup</button>
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

            {{-- Detail grup --}}
            <div class="glass-panel p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="sm:col-span-1">
                    @if ($first->stock_photo_path)
                        <img src="{{ Storage::url($first->stock_photo_path) }}" class="w-full rounded-lg object-cover">
                    @else
                        <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                            Belum ada foto
                        </div>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-500">Warna</dt><dd class="font-medium text-gray-900">{{ $first->color ?: '-' }}</dd></div>
                        <div><dt class="text-gray-500">Outlet</dt><dd class="font-medium text-gray-900">{{ $first->outletLabel() }}</dd></div>
                        <div><dt class="text-gray-500">Ambang Low Stock</dt><dd class="font-medium text-gray-900">{{ $first->low_stock_threshold }}</dd></div>
                        <div><dt class="text-gray-500">Jumlah Ukuran</dt><dd class="font-medium text-gray-900">{{ $items->count() }}</dd></div>
                        <div><dt class="text-gray-500">Total Tersedia</dt><dd class="font-bold text-green-600 text-lg">{{ $totalAvailable }}</dd></div>
                        <div><dt class="text-gray-500">Total Tidak Layak</dt><dd class="font-bold text-red-600 text-lg">{{ $totalUnusable }}</dd></div>
                    </dl>
                </div>
            </div>

            {{-- Per ukuran --}}
            <div class="glass-panel overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Semua Ukuran</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Klik salah satu ukuran untuk Restock atau lihat riwayat movement-nya.</p>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Ukuran</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Kode</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Tersedia</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500">Tidak Layak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ga.uniforms.stocks.show', $item) }}'">
                                <td class="px-6 py-3 font-medium text-gray-800">
                                    Size {{ $item->size ?: '-' }}
                                    @if ($item->isLowStock())
                                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-700">Menipis</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $item->stock_code }}</td>
                                <td class="px-6 py-3 text-right font-semibold {{ $item->isLowStock() ? 'text-orange-600' : 'text-gray-800' }}">{{ $item->available_stock }}</td>
                                <td class="px-6 py-3 text-right text-red-600">{{ $item->unusable_stock }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('ga.uniforms.stocks.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
