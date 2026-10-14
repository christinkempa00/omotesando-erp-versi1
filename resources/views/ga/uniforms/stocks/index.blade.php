<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.records.index')" parent="Inventaris Seragam" current="Manajemen Stok" />
                <h2 class="font-semibold text-xl text-ink leading-tight">
                    Manajemen Stok
                </h2>
            </div>
            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('ga.uniforms.stocks.create') }}"
                       class="inline-flex items-center btn-primary">
                        Buat Varian
                    </a>
                </div>
            @endif
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
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Stok Tersedia</p>
                    <p class="text-2xl font-bold text-ink mt-auto pt-1">{{ $summary['total_available'] }}</p>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Belum Dikembalikan</p>
                    <p class="text-2xl font-bold text-ink mt-auto pt-1">{{ $summary['pending_return'] }}</p>
                    <a href="{{ route('ga.uniforms.records.index', ['status' => \App\Models\GA\UniformRecord::STATUS_ISSUED]) }}" class="text-xs text-action hover:text-action-hover mt-1">
                        Lihat di Inventaris Seragam &rarr;
                    </a>
                </div>
                <div class="card-flat p-4 flex flex-col">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Stok Tidak Layak</p>
                    <p class="text-2xl font-bold text-red-600 mt-auto pt-1">{{ $summary['total_unusable'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('ga.uniforms.stocks.index')" search-name="stock_search" :search-value="$stockSearch" search-placeholder="Cari tipe/kode/warna..." :reset-url="route('ga.uniforms.stocks.index')">
                <div>
                    <label class="block text-xs font-medium text-ink-muted mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-hairline text-sm shadow-sm focus:border-action focus:ring-action">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-filter-pills name="status" label="Kondisi" :options="$statusLabels" :selected="$selectedStatus" />
            </x-filter-bar>

            <div class="card-flat overflow-hidden" x-data="{ view: 'card' }">
                <div class="px-6 py-4 border-b border-hairline flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-xs text-ink-muted">Per kombinasi outlet, jenis seragam, dan warna.</p>
                    <div class="inline-flex rounded-lg border border-hairline overflow-hidden shrink-0">
                        <button type="button" @click="view='card'"
                                :class="view==='card' ? 'bg-action text-white' : 'bg-white text-ink-muted hover:bg-surface'"
                                class="px-3 h-9 text-sm font-medium inline-flex items-center gap-1.5">Kartu</button>
                        <button type="button" @click="view='table'"
                                :class="view==='table' ? 'bg-action text-white' : 'bg-white text-ink-muted hover:bg-surface'"
                                class="px-3 h-9 text-sm font-medium inline-flex items-center gap-1.5 border-l border-hairline">Tabel</button>
                    </div>
                </div>

                <div x-show="view==='card'" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse ($stockGroups as $group)
                        <div class="border rounded-[14px] p-3 cursor-pointer hover:shadow-sm transition {{ $group->is_low ? 'border-status-rejected-fg/30 bg-status-rejected-bg hover:border-status-rejected-fg/50' : 'border-hairline hover:bg-surface' }}"
                             onclick="window.location='{{ route('ga.uniforms.stocks.show-group', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}'">
                            <div class="flex items-start justify-between mb-1">
                                <div class="flex items-start gap-2 min-w-0">
                                    @if ($group->photo_path)
                                        <img src="{{ Storage::url($group->photo_path) }}"
                                             class="w-10 h-10 rounded object-cover shrink-0 border border-hairline">
                                    @else
                                        <div class="w-10 h-10 rounded shrink-0 border border-hairline bg-surface flex items-center justify-center text-ink-muted">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 7a2 2 0 0 1 2-2h1.5l1-1.5h5l1 1.5H19a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                                                <circle cx="12" cy="13" r="3.5" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-ink truncate">{{ $group->type }}</p>
                                        <p class="text-xs text-ink-muted truncate">
                                            {{ $group->branch->name }}{{ $group->branchLocation ? ' — '.$group->branchLocation->name : '' }}{{ $group->color ? ' / '.$group->color : '' }}
                                        </p>
                                    </div>
                                </div>
                                @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                                    <div class="flex items-center gap-1 shrink-0" onclick="event.stopPropagation()">
                                        <a href="{{ route('ga.uniforms.stocks.create', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                           title="Tambah ukuran baru untuk grup ini"
                                           class="w-6 h-6 flex items-center justify-center rounded-md border border-hairline text-ink-muted hover:bg-surface">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 5v14M5 12h14" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('ga.uniforms.stocks.edit-group', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                           title="Edit grup ini (Tipe Seragam/Outlet/Warna, dll, semua ukuran sekaligus)"
                                           class="w-6 h-6 flex items-center justify-center rounded-md border border-hairline text-ink-muted hover:bg-surface">
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
                                                    class="w-6 h-6 flex items-center justify-center rounded-md border border-hairline text-ink-muted hover:bg-status-rejected-bg hover:text-status-rejected-fg">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M6 6l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14M4 6h16M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-2 pb-2 border-b border-hairline text-xs">
                                <div>
                                    <p class="text-ink-muted">Total Tersedia</p>
                                    <p class="font-semibold text-ink text-sm">{{ $group->total_available }}</p>
                                </div>
                                <div>
                                    <p class="text-ink-muted">Tidak Layak</p>
                                    <p class="font-semibold text-red-600 text-sm">{{ $group->total_unusable }}</p>
                                </div>
                            </div>

                            <div class="divide-y divide-hairline" x-data="{ restockOpen: null }" onclick="event.stopPropagation()">
                                @foreach ($group->items as $item)
                                    <div class="py-1.5">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-baseline gap-1.5 min-w-0">
                                                <span class="text-xs font-medium text-ink whitespace-nowrap">Ukuran {{ $item->size ?: '-' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 shrink-0">
                                                <span class="text-xs font-semibold {{ $item->isLowStock() ? 'text-orange-600' : 'text-ink' }}">
                                                    {{ $item->available_stock }}
                                                </span>
                                                @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                                                    <button type="button"
                                                            @click="restockOpen = (restockOpen === {{ $item->id }} ? null : {{ $item->id }})"
                                                            title="Restock ukuran ini"
                                                            class="w-5 h-5 flex items-center justify-center rounded-md border border-hairline text-ink-muted hover:bg-surface">
                                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M12 5v14M5 12h14" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                        @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_STOCKS))
                                            <form x-show="restockOpen === {{ $item->id }}" x-cloak
                                                  method="POST" action="{{ route('ga.uniforms.stocks.restock', $item) }}"
                                                  class="mt-1.5 flex items-center gap-1.5">
                                                @csrf
                                                <input type="number" name="quantity" min="1" required placeholder="Jumlah"
                                                       class="w-full field-input text-xs h-8">
                                                <button type="submit"
                                                        class="shrink-0 px-2.5 h-8 text-xs font-medium rounded-lg bg-action text-white hover:bg-action-hover">
                                                    Tambah
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <p class="text-[10px] text-ink-muted text-right pt-2 mt-1 border-t border-hairline">
                                batas low stock {{ $group->items->first()?->low_stock_threshold ?? 0 }}
                            </p>
                        </div>
                    @empty
                        <p class="col-span-full text-center text-sm text-ink-muted py-8">Belum ada varian seragam.</p>
                    @endforelse
                </div>

                <div x-show="view==='table'" x-cloak style="display:none;" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tipe Seragam</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Warna</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Outlet</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kondisi</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Ukuran</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tidak Layak</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-hairline">
                            @forelse ($stockGroups as $group)
                                <tr class="hover:bg-[#FDFBF4] cursor-pointer"
                                    onclick="window.location='{{ route('ga.uniforms.stocks.show-group', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}'">
                                    <td class="px-4 py-3 text-sm text-center font-medium text-ink">{{ $group->type }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-ink-muted">{{ $group->color ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-ink-muted">{{ $group->branch->name }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($group->is_low)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border border-status-rejected-fg/30 bg-status-rejected-bg text-status-rejected-fg">Perlu Ganti</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border border-status-approved-fg/30 bg-status-approved-bg text-status-approved-fg">Bagus</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-ink-muted">
                                        @foreach ($group->items as $item)
                                            <span class="whitespace-nowrap">{{ $item->size ?: '-' }}:{{ $item->available_stock }}</span>@if (!$loop->last)<span> </span>@endif
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center font-mono text-ink">{{ $group->total_available }}</td>
                                    <td class="px-4 py-3 text-sm text-center font-mono {{ $group->total_unusable > 0 ? 'text-red-600' : 'text-ink' }}">{{ $group->total_unusable }}</td>
                                    <td class="px-4 py-3 text-sm text-center" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-center gap-3">
                                            <a href="{{ route('ga.uniforms.stocks.show-group', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                               class="text-action hover:text-action-hover">Riwayat</a>
                                            <a href="{{ route('ga.uniforms.stocks.create', ['uniform_type' => $group->type, 'branch_id' => $group->branch->id, 'color' => $group->color]) }}"
                                               class="text-action hover:text-action-hover">Tambah stok</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada varian seragam.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-table-footer :paginator="$stockGroups">
                    <x-slot name="export">
                        <div class="relative" x-data="{ exportOpen: false }">
                            <button type="button" @click="exportOpen = !exportOpen"
                                    class="btn-secondary inline-flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16"/>
                                </svg>
                                Export
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                            <div x-show="exportOpen" @click.outside="exportOpen = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white border border-hairline rounded-lg shadow-lg z-20 overflow-hidden"
                                 style="display:none;">
                                <a href="{{ route('ga.uniforms.stocks.export.xlsx', request()->query()) }}"
                                   class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink hover:bg-surface">
                                    <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9"/><path d="m9 12 6 6M15 12l-6 6"/></svg>
                                    Excel (.xlsx)
                                </a>
                                <a href="{{ route('ga.uniforms.stocks.export.pdf', request()->query()) }}"
                                   class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink hover:bg-surface border-t border-hairline">
                                    <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    PDF (.pdf)
                                </a>
                            </div>
                        </div>
                    </x-slot>
                </x-table-footer>
            </div>

            {{-- Audit trail stok (embedded, tanpa halaman terpisah) --}}
            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-ink">Riwayat</h3>
                    <a href="{{ route('ga.uniforms.movements.index') }}" class="text-xs text-action hover:text-action-hover">Lihat Semua &rarr;</a>
                </div>
                <div class="divide-y divide-hairline">
                    @forelse ($recentMovements as $movement)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ \App\Models\GA\UniformMovement::typeBadgeColor($movement->movement_type) }}">
                                    {{ $movementTypeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                </span>
                                <span class="text-ink truncate">
                                    {{ $movement->uniformStock->outletLabel() }} / {{ $movement->uniformStock->uniform_type }}
                                    @if ($movement->uniformStock->size) / {{ $movement->uniformStock->size }} @endif
                                    @if ($movement->uniformStock->color) / {{ $movement->uniformStock->color }} @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-ink-muted shrink-0">
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
                        <p class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada movement.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
