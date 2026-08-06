<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('scm.deliveries.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Surat Jalan {{ $deliveryNote->delivery_code }}
                </h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('scm.deliveries.document', $deliveryNote) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Surat Jalan (PDF)
                </a>
                @if ($deliveryNote->receipt)
                    <a href="{{ route('scm.deliveries.berita-acara', $deliveryNote) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                        Berita Acara (PDF)
                    </a>
                @endif
            </div>
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

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\SCM\DeliveryNote::statusBadgeColor($deliveryNote->status) }}">
                        {{ \App\Models\SCM\DeliveryNote::statusLabels()[$deliveryNote->status] ?? $deliveryNote->status }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Dari</dt>
                        <dd class="text-gray-800 font-medium">{{ $deliveryNote->fromBranch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Ke</dt>
                        <dd class="text-gray-800 font-medium">{{ $deliveryNote->toBranch?->name ?? '—' }}</dd>
                    </div>
                    @if ($deliveryNote->sent_at)
                        <div>
                            <dt class="text-gray-500">Dikirim</dt>
                            <dd class="text-gray-800 font-medium">{{ $deliveryNote->sentBy?->name }} · {{ $deliveryNote->sent_at->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($deliveryNote->receipt)
                        <div>
                            <dt class="text-gray-500">Diterima</dt>
                            <dd class="text-gray-800 font-medium">{{ $deliveryNote->receipt->receivedBy?->name }} · {{ $deliveryNote->receipt->received_at->format('d M Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($deliveryNote->sent_photo_path)
                    <div class="mt-4">
                        <p class="text-xs text-gray-500 mb-1">Foto Bukti Kirim</p>
                        <a href="{{ Storage::url($deliveryNote->sent_photo_path) }}" target="_blank">
                            <img src="{{ Storage::url($deliveryNote->sent_photo_path) }}" class="w-32 h-32 rounded-md object-cover border border-gray-200">
                        </a>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Item</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Label</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Produk</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Qty Dikirim</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Qty Diterima</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($deliveryNote->items as $item)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-gray-600">{{ $item->batchLabel?->label_code }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->batchLabel?->productionBatchItem?->item_name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty_sent }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty_received ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($item->discrepancy)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-status-discrepancy-bg text-status-discrepancy-fg">
                                                {{ $item->discrepancy->qty_diff > 0 ? '+' : '' }}{{ $item->discrepancy->qty_diff }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kirim — Gudang/Admin, hanya saat draft --}}
            @if ($deliveryNote->status === \App\Models\SCM\DeliveryNote::STATUS_DRAFT && auth()->user()->hasRole(\App\Models\Role::GUDANG, \App\Models\Role::ADMIN))
                <div class="bg-white shadow-sm rounded-lg p-6"
                     x-data="{ photoDataUrl: null, onPhotoChange(e) { const f = e.target.files[0]; this.photoDataUrl = f ? URL.createObjectURL(f) : null; } }">
                    <h3 class="font-bold text-ink mb-1">Konfirmasi Kirim</h3>
                    <p class="text-xs text-ink-muted mb-4">Wajib diisi sebelum surat jalan bisa dikirim</p>
                    <form method="POST" action="{{ route('scm.deliveries.send', $deliveryNote) }}" enctype="multipart/form-data" class="space-y-4"
                          onsubmit="return confirm('Kirim surat jalan ini?')">
                        @csrf
                        <input type="file" name="sent_photo" accept="image/*" capture="environment" required
                               x-ref="photoInput" @change="onPhotoChange" class="hidden">

                        <div x-show="!photoDataUrl" @click="$refs.photoInput.click()"
                             class="h-52 rounded-2xl border-2 border-dashed border-accent/40 bg-accent-tint flex flex-col items-center justify-center gap-2.5 cursor-pointer max-w-xs">
                            <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center text-2xl">📷</div>
                            <div class="text-sm font-bold text-accent">Ketuk untuk buka kamera</div>
                            <div class="text-[10.5px] font-bold text-white bg-status-rejected-fg px-2.5 py-1 rounded-full">WAJIB</div>
                        </div>
                        <div x-show="photoDataUrl" x-cloak class="rounded-2xl overflow-hidden border border-hairline max-w-xs">
                            <img :src="photoDataUrl" class="w-full h-44 object-cover">
                            <div class="px-3.5 py-2.5 bg-white flex items-center gap-2">
                                <div class="text-xs font-bold text-status-approved-fg flex-1">✓ Foto tersimpan</div>
                                <button type="button" @click="$refs.photoInput.click()" class="text-xs font-bold text-accent">Ambil ulang</button>
                            </div>
                        </div>

                        <button type="submit" :disabled="!photoDataUrl"
                                class="w-full sm:w-auto sm:px-8 h-[52px] rounded-2xl bg-accent text-white font-bold text-[15px] disabled:opacity-40">
                            Kirim Surat Jalan
                        </button>
                    </form>
                </div>
            @endif

            {{-- Terima — Outlet/Admin, hanya saat sent — wizard mini: Hitung -> Foto -> Konfirmasi --}}
            @if ($deliveryNote->status === \App\Models\SCM\DeliveryNote::STATUS_SENT && auth()->user()->hasRole(\App\Models\Role::OUTLET, \App\Models\Role::ADMIN))
                <div class="bg-white shadow-sm rounded-lg overflow-hidden"
                     x-data="{
                        step: 1,
                        totalSteps: 3,
                        qty: {{ Illuminate\Support\Js::from($deliveryNote->items->mapWithKeys(fn ($i) => [$i->id => $i->qty_sent])) }},
                        expected: {{ Illuminate\Support\Js::from($deliveryNote->items->mapWithKeys(fn ($i) => [$i->id => $i->qty_sent])) }},
                        photoDataUrl: null,
                        adjust(id, delta) { this.qty[id] = Math.max(0, (this.qty[id] ?? 0) + delta); },
                        mismatch(id) { return this.qty[id] !== this.expected[id]; },
                        get hasMismatch() { return Object.keys(this.qty).some(id => this.mismatch(id)); },
                        onPhotoChange(e) { const f = e.target.files[0]; this.photoDataUrl = f ? URL.createObjectURL(f) : null; },
                        next() { if (this.step < this.totalSteps) this.step++; },
                        back() { if (this.step > 1) this.step--; },
                     }">
                    <div class="px-6 pt-5 pb-3 border-b border-hairline">
                        <h3 class="font-bold text-ink" x-text="'Konfirmasi Terima — Langkah ' + step + ' dari ' + totalSteps"></h3>
                        <div class="flex gap-1 mt-2.5 max-w-xs">
                            <template x-for="n in totalSteps" :key="n">
                                <div class="flex-1 h-1 rounded-full" :class="n <= step ? 'bg-accent' : 'bg-hairline'"></div>
                            </template>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('scm.deliveries.receive', $deliveryNote) }}" enctype="multipart/form-data">
                        @csrf
                        @foreach ($deliveryNote->items as $item)
                            <input type="hidden" name="items[{{ $loop->index }}][delivery_note_item_id]" value="{{ $item->id }}">
                            <input type="hidden" :name="'items[{{ $loop->index }}][qty_received]'" :value="qty[{{ $item->id }}]">
                        @endforeach

                        <div class="p-6 min-h-[220px]">
                            {{-- Step 1: hitung barang fisik --}}
                            <div x-show="step === 1" x-cloak>
                                <p class="text-xs text-ink-muted mb-3">Cocokkan jumlah fisik dengan qty di surat jalan.</p>
                                <div class="space-y-2 max-w-md">
                                    @foreach ($deliveryNote->items as $item)
                                        <div class="rounded-xl border p-3"
                                             :class="mismatch({{ $item->id }}) ? 'border-status-discrepancy-fg/40 bg-status-discrepancy-bg' : 'border-hairline bg-white'">
                                            <div class="flex justify-between items-center mb-2">
                                                <div class="text-sm font-semibold text-ink truncate">{{ $item->batchLabel?->productionBatchItem?->item_name }}</div>
                                                <div class="text-xs text-ink-muted">Kirim: <span class="font-mono font-bold">{{ $item->qty_sent }}</span></div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="adjust({{ $item->id }}, -1)"
                                                        class="w-9 h-9 rounded-lg bg-white border border-hairline flex items-center justify-center text-lg font-bold text-ink">−</button>
                                                <div class="flex-1 text-center text-lg font-extrabold font-mono" x-text="qty[{{ $item->id }}]"></div>
                                                <button type="button" @click="adjust({{ $item->id }}, 1)"
                                                        class="w-9 h-9 rounded-lg bg-white border border-hairline flex items-center justify-center text-lg font-bold text-ink">+</button>
                                            </div>
                                            <div x-show="mismatch({{ $item->id }})" x-cloak class="text-xs font-bold text-status-discrepancy-fg mt-2">
                                                ⚠ Selisih <span x-text="qty[{{ $item->id }}] - {{ $item->qty_sent }}"></span> dari surat jalan
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Step 2: foto bukti terima --}}
                            <div x-show="step === 2" x-cloak>
                                <div class="text-sm font-bold text-ink mb-1">Foto bukti terima</div>
                                <p class="text-xs text-ink-muted mb-3.5">Wajib — dokumentasi kondisi barang saat diterima</p>

                                <input type="file" name="received_photo" accept="image/*" capture="environment"
                                       x-ref="photoInput" @change="onPhotoChange" class="hidden">

                                <div x-show="!photoDataUrl" @click="$refs.photoInput.click()"
                                     class="h-52 rounded-2xl border-2 border-dashed border-accent/40 bg-accent-tint flex flex-col items-center justify-center gap-2.5 cursor-pointer max-w-xs">
                                    <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center text-2xl">📷</div>
                                    <div class="text-sm font-bold text-accent">Ketuk untuk buka kamera</div>
                                    <div class="text-[10.5px] font-bold text-white bg-status-rejected-fg px-2.5 py-1 rounded-full">WAJIB</div>
                                </div>
                                <div x-show="photoDataUrl" x-cloak class="rounded-2xl overflow-hidden border border-hairline max-w-xs">
                                    <img :src="photoDataUrl" class="w-full h-44 object-cover">
                                    <div class="px-3.5 py-2.5 bg-white flex items-center gap-2">
                                        <div class="text-xs font-bold text-status-approved-fg flex-1">✓ Foto tersimpan</div>
                                        <button type="button" @click="$refs.photoInput.click()" class="text-xs font-bold text-accent">Ambil ulang</button>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 3: catatan & konfirmasi --}}
                            <div x-show="step === 3" x-cloak>
                                <div x-show="hasMismatch" x-cloak class="bg-status-discrepancy-bg border border-status-discrepancy-fg/30 rounded-xl p-3.5 mb-4 max-w-md">
                                    <div class="text-xs font-extrabold text-status-discrepancy-fg">⚠ Laporan Selisih akan dibuat otomatis</div>
                                    <div class="text-xs text-status-discrepancy-fg/90 mt-1 leading-relaxed">Sistem mendeteksi selisih qty dan langsung membuat laporan selisih untuk ditinjau — Anda tidak perlu isi form tambahan.</div>
                                </div>
                                <label class="block text-sm font-medium text-ink mb-1">Catatan</label>
                                <textarea name="notes" rows="2" placeholder="Catatan (opsional)" class="w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-accent focus:ring-accent"></textarea>
                            </div>
                        </div>

                        <div class="p-4 border-t border-hairline flex gap-2.5">
                            <button type="button" x-show="step > 1" @click="back()"
                                    class="px-5 h-[52px] rounded-2xl border border-hairline text-ink font-bold text-sm">
                                Kembali
                            </button>
                            <button type="button" x-show="step === 1" @click="next()"
                                    class="flex-1 sm:flex-none sm:px-8 h-[52px] rounded-2xl bg-accent text-white font-bold text-[15px]">
                                Lanjut
                            </button>
                            <button type="button" x-show="step === 2" @click="next()" :disabled="!photoDataUrl"
                                    class="flex-1 sm:flex-none sm:px-8 h-[52px] rounded-2xl bg-accent text-white font-bold text-[15px] disabled:opacity-40">
                                Lanjut
                            </button>
                            <button type="submit" x-show="step === 3" onclick="return confirm('Konfirmasi penerimaan barang ini?')"
                                    class="flex-1 sm:flex-none sm:px-8 h-[52px] rounded-2xl bg-accent text-white font-bold text-[15px]">
                                Selesai
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($deliveryNote->receipt?->received_photo_path)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-xs text-gray-500 mb-1">Foto Bukti Terima</p>
                    <a href="{{ Storage::url($deliveryNote->receipt->received_photo_path) }}" target="_blank">
                        <img src="{{ Storage::url($deliveryNote->receipt->received_photo_path) }}" class="w-32 h-32 rounded-md object-cover border border-gray-200">
                    </a>
                    @if ($deliveryNote->receipt->notes)
                        <p class="text-sm text-gray-600 mt-3">{{ $deliveryNote->receipt->notes }}</p>
                    @endif
                </div>
            @endif

            <div>
                <a href="{{ route('scm.deliveries.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
