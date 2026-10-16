@php
    $asset = $asset ?? null;
    $submitLabel = $submitLabel ?? 'Simpan';
    $cancelUrl = $cancelUrl ?? route('ga.assets.index');
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="{
        purchasePrice: {{ (float) old('purchase_price', $asset->purchase_price ?? 0) }},
        depreciationValue: {{ (float) old('depreciation_value', $asset->depreciation_value ?? 0) }},
        branchId: '{{ old('branch_id', $asset->branch_id ?? '') }}',
        cabangSummary: {{ Js::from($asset?->branchLocation?->name) }},
        formatThousands(n) { return (Number(n) || 0).toLocaleString('id-ID'); },
        parseThousands(str) { return Number(String(str).replace(/[^\d]/g, '')) || 0; }
     }"
     x-on:branch-location-changed.window="cabangSummary = $event.detail.name">
    {{-- Kolom kiri: data aset --}}
    <div class="lg:col-span-2 card-flat p-6 space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-ink mb-1">Nama Aset *</label>
                <input type="text" name="name" required value="{{ old('name', $asset->name ?? '') }}" placeholder="Contoh: Chiller Kitchen"
                       onkeyup="document.getElementById('summary-nama').textContent = this.value || '-'"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Merk</label>
                <input type="text" name="brand" value="{{ old('brand', $asset->brand ?? '') }}" placeholder="Contoh: Modena"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Nomor Serial (SN)</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}"
                       placeholder="Masukkan nomor serial (opsional)"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Outlet *</label>
                <select name="branch_id" id="branch_id" required
                        x-model="branchId"
                        @change="window.dispatchEvent(new CustomEvent('outlet-changed', { detail: { branchId } }))"
                        onchange="document.getElementById('summary-outlet').textContent = this.options[this.selectedIndex].text || '-'"
                        class="w-full field-input">
                    <option value="">-- Pilih Outlet --</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $asset->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <x-branch-location-select
                :selected="old('branch_location_id', $asset->branch_location_id ?? null)"
                :initial-branch-id="old('branch_id', $asset->branch_id ?? null)"
                store-route="ga.branch-locations.store"
                emit-event="branch-location-changed"
                outlet-listen-event="outlet-changed" />

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Lokasi</label>
                <select name="location" id="location"
                        onchange="document.getElementById('summary-lokasi').textContent = this.value || '-'"
                        class="w-full field-input">
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach (['Kitchen', 'Floor', 'Bar', 'Lainnya'] as $loc)
                        <option value="{{ $loc }}" @selected(old('location', $asset->location ?? '') === $loc)>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Status *</label>
                <select name="status" required class="w-full field-input">
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $asset->status ?? 'bagus') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Staf Penanggung Jawab *</label>
                <input type="text" name="custodian_name" required value="{{ old('custodian_name', $asset->custodian_name ?? '') }}" placeholder="Nama staf"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">No SP3</label>
                <input type="text" name="sp3_number" value="{{ old('sp3_number', $asset->sp3_number ?? '') }}" placeholder="Masukkan nomor SP3"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">No PO</label>
                <input type="text" name="po_number" value="{{ old('po_number', $asset->po_number ?? '') }}" placeholder="Masukkan nomor PO"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Tanggal Beli</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($asset->purchase_date ?? null)->format('Y-m-d')) }}"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Tanggal Penerimaan</label>
                <input type="date" name="receive_date" value="{{ old('receive_date', optional($asset->receive_date ?? null)->format('Y-m-d')) }}"
                       class="w-full field-input">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Harga Beli</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm pointer-events-none">Rp</span>
                    <input type="text" inputmode="numeric"
                           :value="formatThousands(purchasePrice)"
                           @input="purchasePrice = parseThousands($event.target.value)"
                           placeholder="0"
                           class="w-full field-input pl-10">
                </div>
                <input type="hidden" name="purchase_price" :value="purchasePrice">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Nilai Depresiasi</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm pointer-events-none">Rp</span>
                    <input type="text" inputmode="numeric"
                           :value="formatThousands(depreciationValue)"
                           @input="depreciationValue = parseThousands($event.target.value)"
                           placeholder="0"
                           class="w-full field-input pl-10">
                </div>
                <input type="hidden" name="depreciation_value" :value="depreciationValue">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-ink mb-1">Dimensi P x L x T (cm)</label>
                <div class="grid grid-cols-3 gap-3">
                    <input type="number" step="0.01" min="0" name="dimension_p" placeholder="Panjang" value="{{ old('dimension_p', $asset->dimension_p ?? '') }}"
                           class="w-full field-input">
                    <input type="number" step="0.01" min="0" name="dimension_l" placeholder="Lebar" value="{{ old('dimension_l', $asset->dimension_l ?? '') }}"
                           class="w-full field-input">
                    <input type="number" step="0.01" min="0" name="dimension_t" placeholder="Tinggi" value="{{ old('dimension_t', $asset->dimension_t ?? '') }}"
                           class="w-full field-input">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Jumlah *</label>
                <input type="number" min="1" name="quantity" required value="{{ old('quantity', $asset->quantity ?? 1) }}"
                       class="w-full field-input">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-ink mb-1">Catatan</label>
                <textarea name="notes" rows="3" placeholder="Tambahkan catatan kondisi, kelengkapan, atau informasi lain."
                          class="w-full rounded-lg border-hairline focus:border-action focus:ring-action">{{ old('notes', $asset->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Kolom kanan: foto & ringkasan --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="card-flat p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div x-data="{ previewUrl: null, fileName: null }">
                    <h3 class="text-sm font-semibold text-ink mb-2">Foto Aset *</h3>

                    <div class="relative w-24 h-24 mb-2">
                        <img :src="previewUrl ?? '{{ ! empty($asset?->image_path) ? Storage::url($asset->image_path) : '' }}'"
                             x-show="previewUrl || {{ ! empty($asset?->image_path) ? 'true' : 'false' }}"
                             class="w-24 h-24 rounded object-cover border border-hairline">
                        <div x-show="!previewUrl && {{ ! empty($asset?->image_path) ? 'false' : 'true' }}"
                             class="w-24 h-24 rounded border border-dashed border-hairline flex items-center justify-center text-ink-muted text-xs">
                            Belum ada
                        </div>
                        <span x-show="previewUrl" x-cloak
                              class="absolute -top-1.5 -right-1.5 bg-action text-white text-[10px] font-medium px-1.5 py-0.5 rounded-full shadow">Baru</span>
                    </div>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-action hover:text-action-hover">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" />
                            <circle cx="12" cy="13" r="3.2" />
                        </svg>
                        <span x-text="fileName ? 'Ganti lagi' : '{{ ! empty($asset?->image_path) ? 'Ganti Foto' : 'Pilih Foto' }}'"></span>
                        <input type="file" name="image" accept="image/*" @if (empty($asset?->image_path)) required @endif
                               class="hidden"
                               @change="
                                   const file = $event.target.files[0];
                                   if (file) {
                                       fileName = file.name;
                                       const reader = new FileReader();
                                       reader.onload = (e) => previewUrl = e.target.result;
                                       reader.readAsDataURL(file);
                                   }
                               ">
                    </label>
                    <p x-show="fileName" x-cloak class="text-[11px] text-ink-muted mt-1">
                        Foto baru: <span x-text="fileName" class="font-medium text-ink"></span> — akan menggantikan foto lama setelah disimpan.
                    </p>
                </div>

                <div x-data="{ previewUrl: null, fileName: null }">
                    <h3 class="text-sm font-semibold text-ink mb-2">Foto SN *</h3>

                    <div class="relative w-24 h-24 mb-2">
                        <img :src="previewUrl ?? '{{ ! empty($asset?->serial_number_photo_path) ? Storage::url($asset->serial_number_photo_path) : '' }}'"
                             x-show="previewUrl || {{ ! empty($asset?->serial_number_photo_path) ? 'true' : 'false' }}"
                             class="w-24 h-24 rounded object-cover border border-hairline">
                        <div x-show="!previewUrl && {{ ! empty($asset?->serial_number_photo_path) ? 'false' : 'true' }}"
                             class="w-24 h-24 rounded border border-dashed border-hairline flex items-center justify-center text-ink-muted text-xs">
                            Belum ada
                        </div>
                        <span x-show="previewUrl" x-cloak
                              class="absolute -top-1.5 -right-1.5 bg-action text-white text-[10px] font-medium px-1.5 py-0.5 rounded-full shadow">Baru</span>
                    </div>

                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-action hover:text-action-hover">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" />
                            <circle cx="12" cy="13" r="3.2" />
                        </svg>
                        <span x-text="fileName ? 'Ganti lagi' : '{{ ! empty($asset?->serial_number_photo_path) ? 'Ganti Foto' : 'Pilih Foto' }}'"></span>
                        <input type="file" name="serial_number_photo" accept="image/*" @if (empty($asset?->serial_number_photo_path)) required @endif
                               class="hidden"
                               @change="
                                   const file = $event.target.files[0];
                                   if (file) {
                                       fileName = file.name;
                                       const reader = new FileReader();
                                       reader.onload = (e) => previewUrl = e.target.result;
                                       reader.readAsDataURL(file);
                                   }
                               ">
                    </label>
                    <p x-show="fileName" x-cloak class="text-[11px] text-ink-muted mt-1">
                        Foto baru: <span x-text="fileName" class="font-medium text-ink"></span> — akan menggantikan foto lama setelah disimpan.
                    </p>
                </div>
            </div>
        </div>

        <div class="card-flat p-6">
            <h3 class="text-sm font-semibold text-ink mb-3">Ringkasan</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-ink-muted">Nama Aset</dt>
                    <dd id="summary-nama" class="font-medium text-ink">{{ $asset->name ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-muted">Outlet</dt>
                    <dd id="summary-outlet" class="font-medium text-ink">{{ $asset->branch->name ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-muted">Cabang</dt>
                    <dd class="font-medium text-ink" x-text="cabangSummary || '-'"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-ink-muted">Lokasi</dt>
                    <dd id="summary-lokasi" class="font-medium text-ink">{{ $asset->location ?? '-' }}</dd>
                </div>
            </dl>

            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-hairline">
                <a href="{{ $cancelUrl }}" class="px-4 py-2 text-sm font-medium text-ink-muted hover:text-ink">Batal</a>
                <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</div>
