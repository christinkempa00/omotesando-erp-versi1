<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.stocks.index')" parent="Manajemen Stok" current="Detail Grup" />
                <h2 class="font-semibold text-xl text-ink leading-tight">
                    {{ $first->uniform_type }}
                    <span class="ml-2 text-sm text-ink-muted font-normal">{{ $first->outletLabel() }}{{ $first->color ? ' / '.$first->color : '' }}</span>
                </h2>
            </div>
            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('ga.uniforms.stocks.edit-group', ['uniform_type' => $first->uniform_type, 'branch_id' => $first->branch_id, 'color' => $first->color]) }}"
                       class="btn-secondary">Edit Grup</a>
                    <form method="POST" action="{{ route('ga.uniforms.stocks.destroy-group') }}"
                          onsubmit="return confirm('Hapus seluruh grup {{ $first->uniform_type }} ({{ $first->branch->name }}{{ $first->color ? ' / '.$first->color : '' }})? Semua ukuran &amp; riwayat movement-nya ikut terhapus.');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="uniform_type" value="{{ $first->uniform_type }}">
                        <input type="hidden" name="branch_id" value="{{ $first->branch_id }}">
                        <input type="hidden" name="color" value="{{ $first->color }}">
                        <button type="submit" class="btn-danger">Hapus Grup</button>
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
            <div class="card-flat p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="sm:col-span-1">
                    @if ($first->stock_photo_path)
                        <img src="{{ Storage::url($first->stock_photo_path) }}" class="w-full rounded-lg object-cover">
                    @else
                        <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-ink-muted text-sm">
                            Belum ada foto
                        </div>
                    @endif
                </div>
                <div class="sm:col-span-2">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-ink-muted">Warna</dt><dd class="font-medium text-ink">{{ $first->color ?: '-' }}</dd></div>
                        <div><dt class="text-ink-muted">Outlet</dt><dd class="font-medium text-ink">{{ $first->outletLabel() }}</dd></div>
                        <div><dt class="text-ink-muted">Ambang Low Stock</dt><dd class="font-medium text-ink">{{ $first->low_stock_threshold }}</dd></div>
                        <div><dt class="text-ink-muted">Jumlah Ukuran</dt><dd class="font-medium text-ink">{{ $items->count() }}</dd></div>
                        <div><dt class="text-ink-muted">Total Tersedia</dt><dd class="font-bold text-green-600 text-lg">{{ $totalAvailable }}</dd></div>
                        <div><dt class="text-ink-muted">Total Tidak Layak</dt><dd class="font-bold text-red-600 text-lg">{{ $totalUnusable }}</dd></div>
                    </dl>
                </div>
            </div>

            {{-- Per ukuran --}}
            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline">
                    <h3 class="text-sm font-semibold text-ink">Semua Ukuran</h3>
                    <p class="text-xs text-ink-muted mt-0.5">Klik salah satu ukuran untuk Restock atau lihat riwayat movement-nya.</p>
                </div>
                <table class="min-w-full divide-y divide-hairline text-sm">
                    <thead class="border-b border-hairline">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Ukuran</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tersedia</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tidak Layak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline">
                        @foreach ($items as $item)
                            <tr class="hover:bg-[#FDFBF4] cursor-pointer" onclick="window.location='{{ route('ga.uniforms.stocks.show', $item) }}'">
                                <td class="px-4 py-3 font-medium text-ink">
                                    Size {{ $item->size ?: '-' }}
                                    @if ($item->isLowStock())
                                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-orange-100 text-orange-700">Menipis</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $item->stock_code }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $item->isLowStock() ? 'text-orange-600' : 'text-ink' }}">{{ $item->available_stock }}</td>
                                <td class="px-4 py-3 text-right text-red-600">{{ $item->unusable_stock }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
