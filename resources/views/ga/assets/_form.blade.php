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
        branchLocationId: '{{ old('branch_location_id', $asset->branch_location_id ?? '') }}',
        branchLocations: @js($branchLocations),
        get availableBranchLocations() { return this.branchLocations[this.branchId] || []; },
        formatThousands(n) { return (Number(n) || 0).toLocaleString('id-ID'); },
        parseThousands(str) { return Number(String(str).replace(/[^\d]/g, '')) || 0; }
     }">
    {{-- Kolom kiri: data aset --}}
    <div class="lg:col-span-2 glass-panel p-6 space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Aset *</label>
                <input type="text" name="name" required value="{{ old('name', $asset->name ?? '') }}" placeholder="Contoh: Chiller Kitchen"
                       onkeyup="document.getElementById('summary-nama').textContent = this.value || '-'"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Merk</label>
                <input type="text" name="brand" value="{{ old('brand', $asset->brand ?? '') }}" placeholder="Contoh: Modena"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Serial (SN)</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}"
                       placeholder="Masukkan nomor serial (opsional)"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                <select name="branch_id" id="branch_id" required
                        x-model="branchId" @change="branchLocationId = ''"
                        onchange="document.getElementById('summary-outlet').textContent = this.options[this.selectedIndex].text || '-'"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                    <option value="">-- Pilih Outlet --</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $asset->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div x-show="availableBranchLocations.length > 0" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
                <select name="branch_location_id" x-model="branchLocationId"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                    <option value="">-- Pilih Cabang --</option>
                    <template x-for="loc in availableBranchLocations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                <select name="location" id="location"
                        onchange="document.getElementById('summary-lokasi').textContent = this.value || '-'"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach (['Kitchen', 'Floor', 'Bar', 'Lainnya'] as $loc)
                        <option value="{{ $loc }}" @selected(old('location', $asset->location ?? '') === $loc)>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $asset->status ?? 'bagus') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Staf Penanggung Jawab *</label>
                <input type="text" name="custodian_name" required value="{{ old('custodian_name', $asset->custodian_name ?? '') }}" placeholder="Nama staf"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No SP3</label>
                <input type="text" name="sp3_number" value="{{ old('sp3_number', $asset->sp3_number ?? '') }}" placeholder="Masukkan nomor SP3"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No PO</label>
                <input type="text" name="po_number" value="{{ old('po_number', $asset->po_number ?? '') }}" placeholder="Masukkan nomor PO"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Beli</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($asset->purchase_date ?? null)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan</label>
                <input type="date" name="receive_date" value="{{ old('receive_date', optional($asset->receive_date ?? null)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">Rp</span>
                    <input type="text" inputmode="numeric"
                           :value="formatThousands(purchasePrice)"
                           @input="purchasePrice = parseThousands($event.target.value)"
                           placeholder="0"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500 pl-10">
                </div>
                <input type="hidden" name="purchase_price" :value="purchasePrice">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Depresiasi</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">Rp</span>
                    <input type="text" inputmode="numeric"
                           :value="formatThousands(depreciationValue)"
                           @input="depreciationValue = parseThousands($event.target.value)"
                           placeholder="0"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500 pl-10">
                </div>
                <input type="hidden" name="depreciation_value" :value="depreciationValue">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dimensi P x L x T (cm)</label>
                <div class="grid grid-cols-3 gap-3">
                    <input type="number" step="0.01" min="0" name="dimension_p" placeholder="Panjang" value="{{ old('dimension_p', $asset->dimension_p ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                    <input type="number" step="0.01" min="0" name="dimension_l" placeholder="Lebar" value="{{ old('dimension_l', $asset->dimension_l ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                    <input type="number" step="0.01" min="0" name="dimension_t" placeholder="Tinggi" value="{{ old('dimension_t', $asset->dimension_t ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah *</label>
                <input type="number" min="1" name="quantity" required value="{{ old('quantity', $asset->quantity ?? 1) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="3" placeholder="Tambahkan catatan kondisi, kelengkapan, atau informasi lain."
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500">{{ old('notes', $asset->notes ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Kolom kanan: foto & ringkasan --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="glass-panel p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Foto Aset *</h3>
                    @if (! empty($asset?->image_path))
                        <img src="{{ Storage::url($asset->image_path) }}" class="w-24 h-24 rounded object-cover mb-2">
                    @endif
                    <input type="file" name="image" accept="image/*" @if (empty($asset?->image_path)) required @endif
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100">
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Foto SN *</h3>
                    @if (! empty($asset?->serial_number_photo_path))
                        <img src="{{ Storage::url($asset->serial_number_photo_path) }}" class="w-24 h-24 rounded object-cover mb-2">
                    @endif
                    <input type="file" name="serial_number_photo" accept="image/*" @if (empty($asset?->serial_number_photo_path)) required @endif
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100">
                </div>
            </div>
        </div>

        <div class="glass-panel p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Ringkasan</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Nama Aset</dt>
                    <dd id="summary-nama" class="font-medium text-gray-800">{{ $asset->name ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Outlet</dt>
                    <dd id="summary-outlet" class="font-medium text-gray-800">{{ $asset->branch->name ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between" x-show="availableBranchLocations.length > 0" x-cloak>
                    <dt class="text-gray-500">Cabang</dt>
                    <dd class="font-medium text-gray-800" x-text="(availableBranchLocations.find(l => l.id == branchLocationId)?.name) || '-'"></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Lokasi</dt>
                    <dd id="summary-lokasi" class="font-medium text-gray-800">{{ $asset->location ?? '-' }}</dd>
                </div>
            </dl>

            <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <a href="{{ $cancelUrl }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                <button type="submit" class="btn-gold">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</div>
