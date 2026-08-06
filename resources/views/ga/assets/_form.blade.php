@php
    $asset = $asset ?? null;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Kolom kiri: data aset --}}
    <div class="lg:col-span-2 bg-white shadow-sm rounded-lg p-6 space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                <select name="branch_id" id="branch_id" required
                        onchange="document.getElementById('summary-outlet').textContent = this.options[this.selectedIndex].text || '-'"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Pilih Outlet --</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $asset->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                <select name="location" id="location"
                        onchange="document.getElementById('summary-lokasi').textContent = this.value || '-'"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach (['Kitchen', 'Floor', 'Bar'] as $loc)
                        <option value="{{ $loc }}" @selected(old('location', $asset->location ?? '') === $loc)>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi *</label>
                <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $asset->status ?? 'bagus') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Harga Beli</label>
                <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $asset->purchase_price ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No SP3</label>
                <input type="text" name="sp3_number" value="{{ old('sp3_number', $asset->sp3_number ?? '') }}" placeholder="Masukkan nomor SP3"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No PO</label>
                <input type="text" name="po_number" value="{{ old('po_number', $asset->po_number ?? '') }}" placeholder="Masukkan nomor PO"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Beli</label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($asset->purchase_date ?? null)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan</label>
                <input type="date" name="receive_date" value="{{ old('receive_date', optional($asset->receive_date ?? null)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dimensi P x L x T (cm)</label>
                <div class="grid grid-cols-3 gap-3">
                    <input type="number" step="0.01" min="0" name="dimension_p" placeholder="Panjang" value="{{ old('dimension_p', $asset->dimension_p ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="number" step="0.01" min="0" name="dimension_l" placeholder="Lebar" value="{{ old('dimension_l', $asset->dimension_l ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="number" step="0.01" min="0" name="dimension_t" placeholder="Tinggi" value="{{ old('dimension_t', $asset->dimension_t ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Merk</label>
                <input type="text" name="brand" value="{{ old('brand', $asset->brand ?? '') }}" placeholder="Contoh: Modena"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Aset *</label>
                <input type="text" name="name" required value="{{ old('name', $asset->name ?? '') }}" placeholder="Contoh: Chiller Kitchen"
                       onkeyup="document.getElementById('summary-nama').textContent = this.value || '-'"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Staf Penanggung Jawab</label>
                <input type="text" name="custodian_name" value="{{ old('custodian_name', $asset->custodian_name ?? '') }}" placeholder="Nama staf"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah *</label>
                <input type="number" min="1" name="quantity" required value="{{ old('quantity', $asset->quantity ?? 1) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Depresiasi</label>
                <input type="number" step="0.01" min="0" name="depreciation_value" value="{{ old('depreciation_value', $asset->depreciation_value ?? '') }}" placeholder="Rp 0"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="3" placeholder="Tambahkan catatan kondisi, kelengkapan, atau informasi lain."
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $asset->notes ?? '') }}</textarea>
            </div>
        </div>

        {{-- Serial Number Aset --}}
        <div class="border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3"># Serial Number Aset</h3>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Serial (SN)</label>
            <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}"
                   placeholder="Masukkan nomor seri (opsional)"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    {{-- Kolom kanan: foto & ringkasan --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Foto Aset</h3>
                    <div class="flex gap-1 mb-2">
                        <button type="button" onclick="document.getElementById('image-input').removeAttribute('capture'); document.getElementById('image-input').click()"
                                class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50">Galeri</button>
                        <button type="button" onclick="document.getElementById('image-input').setAttribute('capture','environment'); document.getElementById('image-input').click()"
                                class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50">Kamera</button>
                    </div>
                    <input type="file" id="image-input" name="image" accept="image/*" class="hidden" onchange="assetPreviewImage(this, 'image-preview')">
                    <div id="image-preview" class="aspect-square rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden">
                        @if (! empty($asset?->image_path))
                            <img src="{{ Storage::url($asset->image_path) }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-xs text-gray-400 text-center px-1">Belum ada gambar</span>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Foto SN</h3>
                    <div class="flex gap-1 mb-2">
                        <button type="button" onclick="document.getElementById('sn-image-input').removeAttribute('capture'); document.getElementById('sn-image-input').click()"
                                class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50">Galeri</button>
                        <button type="button" onclick="document.getElementById('sn-image-input').setAttribute('capture','environment'); document.getElementById('sn-image-input').click()"
                                class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50">Kamera</button>
                    </div>
                    <input type="file" id="sn-image-input" name="serial_number_photo" accept="image/*" class="hidden" onchange="assetPreviewImage(this, 'sn-image-preview')">
                    <div id="sn-image-preview" class="aspect-square rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden">
                        @if (! empty($asset?->serial_number_photo_path))
                            <img src="{{ Storage::url($asset->serial_number_photo_path) }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-xs text-gray-400 text-center px-1">Belum ada gambar</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
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
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Lokasi</dt>
                    <dd id="summary-lokasi" class="font-medium text-gray-800">{{ $asset->location ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<script>
    function assetPreviewImage(input, targetId) {
        const target = document.getElementById(targetId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                target.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
