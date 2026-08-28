@php
    // $gaRequest null => form create baru. Kalau diisi (edit draft), semua
    // field di-prefill dari model (di-override old() kalau validasi gagal).
    $defaultItems = [['item_name' => '', 'type' => '', 'unit' => 'Pcs', 'qty' => 1, 'price_per_unit' => 0, 'vendor_name' => '']];
    $initialItems = $gaRequest
        ? $gaRequest->items->map(fn ($item) => [
            'item_name' => $item->item_name,
            'type' => $item->type,
            'unit' => $item->unit,
            'qty' => $item->qty,
            'price_per_unit' => (float) $item->price_per_unit,
            'vendor_name' => $item->vendor_name,
        ])->values()->all()
        : $defaultItems;

    $initialDiscountPercent = old('discount_percent', $gaRequest?->discount_percent);
    $initialPphPercent = old('pph_percent', $gaRequest?->pph_percent);
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data"
      x-data="{
        branchId: '{{ old('branch_id', $gaRequest?->branch_id) }}',
        branchLocationId: '{{ old('branch_location_id', $gaRequest?->branch_location_id) }}',
        branchLocations: @js($branchLocations),
        get availableBranchLocations() { return this.branchLocations[this.branchId] || []; },
        items: {{ Illuminate\Support\Js::from(old('items', $initialItems)) }},
        attachmentPreviews: [],
        discountEnabled: {{ Illuminate\Support\Js::from($initialDiscountPercent !== null) }},
        discountPercent: {{ Illuminate\Support\Js::from($initialDiscountPercent !== null ? (float) $initialDiscountPercent : 0) }},
        pphEnabled: {{ Illuminate\Support\Js::from($initialPphPercent !== null) }},
        pphPercent: {{ Illuminate\Support\Js::from($initialPphPercent !== null ? (float) $initialPphPercent : 0) }},
        get subtotal() {
            return this.items.reduce((sum, i) => sum + (Number(i.qty || 0) * Number(i.price_per_unit || 0)), 0);
        },
        get discountAmount() {
            return this.discountEnabled ? this.subtotal * (Number(this.discountPercent || 0) / 100) : 0;
        },
        get pphAmount() {
            return this.pphEnabled ? this.subtotal * (Number(this.pphPercent || 0) / 100) : 0;
        },
        get grandTotal() {
            return this.subtotal - this.discountAmount - this.pphAmount;
        },
        rupiah(n) { return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID'); },
        formatThousands(n) { return (Number(n) || 0).toLocaleString('id-ID'); },
        parseThousands(str) { return Number(String(str).replace(/[^\d]/g, '')) || 0; },
        onAttachmentsChange(event) {
            this.attachmentPreviews = Array.from(event.target.files).map(f => URL.createObjectURL(f));
        }
      }">
    @csrf
    @if ($gaRequest)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pemohon *</label>
            <input type="text" name="requester_name" required value="{{ old('requester_name', $gaRequest?->requester_name) }}" placeholder="Nama Pemohon"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
            <select name="branch_id" required x-model="branchId" @change="branchLocationId = ''" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- Pilih Outlet --</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $gaRequest?->branch_id) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <div x-show="availableBranchLocations.length > 0" x-cloak>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
            <select name="branch_location_id" x-model="branchLocationId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- Pilih Cabang --</option>
                <template x-for="loc in availableBranchLocations" :key="loc.id">
                    <option :value="loc.id" x-text="loc.name"></option>
                </template>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori / Tujuan *</label>
            <select name="category" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($categoryLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $gaRequest?->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas *</label>
            <select name="priority" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($priorityLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $gaRequest?->priority ?? 'normal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Keterangan *</label>
            <textarea name="description" rows="3" required
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $gaRequest?->description) }}</textarea>
        </div>
    </div>

    {{-- Item dinamis --}}
    <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Daftar Item *</label>
        <div class="space-y-3">
            <template x-for="(item, index) in items" :key="index">
                <div class="relative grid grid-cols-12 gap-2 items-start border border-gray-100 rounded-md p-3 pr-8">
                    <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                            class="absolute top-2 right-2 px-1.5 py-1 text-sm text-red-600 hover:bg-red-50 rounded-md">✕</button>
                    <div class="col-span-12 sm:col-span-3">
                        <input type="text" :name="`items[${index}][item_name]`" x-model="item.item_name"
                               placeholder="Nama item" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <input type="text" :name="`items[${index}][type]`" x-model="item.type"
                               placeholder="Tipe"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <input type="number" min="1" :name="`items[${index}][qty]`" x-model="item.qty"
                               placeholder="Qty" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    </div>
                    <div class="col-span-3 sm:col-span-1">
                        <input type="text" :name="`items[${index}][unit]`" x-model="item.unit"
                               placeholder="Satuan" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">Rp</span>
                            <input type="text" inputmode="numeric"
                                   :value="formatThousands(item.price_per_unit)"
                                   @input="item.price_per_unit = parseThousands($event.target.value)"
                                   placeholder="0"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm pl-10">
                        </div>
                        <input type="hidden" :name="`items[${index}][price_per_unit]`" :value="item.price_per_unit">
                    </div>
                    <div class="col-span-12 sm:col-span-2">
                        <input type="text" :name="`items[${index}][vendor_name]`" x-model="item.vendor_name"
                               placeholder="Vendor"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <label class="block text-xs text-gray-400 mb-1">Total</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">Rp</span>
                            <input type="text" readonly tabindex="-1"
                                   :value="formatThousands((item.qty || 0) * (item.price_per_unit || 0))"
                                   class="w-full rounded-md border-gray-200 bg-gray-50 text-gray-600 shadow-sm text-sm pl-10 cursor-default">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" @click="items.push({ item_name: '', type: '', unit: 'Pcs', qty: 1, price_per_unit: 0, vendor_name: '' })"
                class="mt-2 inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
            Buat item
        </button>
        <p class="text-xs text-gray-400 mt-1">Harga Satuan tiap item wajib diisi saat "Kirim Pengajuan" (boleh 0 dulu utk semua item selama masih disimpan sbg draft).</p>

        {{-- PPH & Diskon (opsional, persentase dari Sub Total) --}}
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" @click="discountEnabled = true" x-show="!discountEnabled"
                    class="inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
                Buat Diskon
            </button>
            <button type="button" @click="pphEnabled = true" x-show="!pphEnabled"
                    class="inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
                Buat PPH
            </button>
        </div>

        <div class="mt-2 space-y-2">
            <template x-if="discountEnabled">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 w-24 shrink-0">Diskon (%)</label>
                    <input type="number" min="0" max="100" step="0.01" name="discount_percent" x-model="discountPercent"
                           class="w-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <span class="text-xs text-gray-500">&minus; <span x-text="rupiah(discountAmount)"></span></span>
                    <button type="button" @click="discountEnabled = false; discountPercent = 0" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                </div>
            </template>
            <template x-if="pphEnabled">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 w-24 shrink-0">PPH (%)</label>
                    <input type="number" min="0" max="100" step="0.01" name="pph_percent" x-model="pphPercent"
                           class="w-28 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <span class="text-xs text-gray-500">&minus; <span x-text="rupiah(pphAmount)"></span></span>
                    <button type="button" @click="pphEnabled = false; pphPercent = 0" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                </div>
            </template>
        </div>

        <div class="mt-4 space-y-1 text-right">
            <div class="text-sm text-gray-500">
                Sub Total: <span class="text-gray-700 font-medium" x-text="rupiah(subtotal)"></span>
            </div>
            <template x-if="discountEnabled">
                <div class="text-sm text-gray-500">
                    Diskon (<span x-text="discountPercent"></span>%): <span class="text-gray-700 font-medium">&minus; <span x-text="rupiah(discountAmount)"></span></span>
                </div>
            </template>
            <template x-if="pphEnabled">
                <div class="text-sm text-gray-500">
                    PPH (<span x-text="pphPercent"></span>%): <span class="text-gray-700 font-medium">&minus; <span x-text="rupiah(pphAmount)"></span></span>
                </div>
            </template>
            <div class="pt-1 border-t border-gray-100">
                <span class="text-sm text-gray-500">Total:</span>
                <span class="text-lg font-bold text-gray-800 ml-2" x-text="rupiah(grandTotal)"></span>
            </div>
        </div>
    </div>

    {{-- Lampiran foto (opsional) --}}
    <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran Foto</label>
        <p class="text-xs text-gray-400 mb-2">Opsional — bisa lampirkan lebih dari satu foto.</p>
        @if ($gaRequest && $gaRequest->attachments->isNotEmpty())
            <div class="mb-3 grid grid-cols-4 sm:grid-cols-6 gap-2">
                @foreach ($gaRequest->attachments as $attachment)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-md object-cover border border-gray-200">
                @endforeach
            </div>
            <p class="text-xs text-gray-400 mb-2">Foto di atas sudah tersimpan — unggah di bawah kalau mau menambah lagi.</p>
        @endif
        <input type="file" name="attachments[]" multiple accept="image/*" @change="onAttachmentsChange($event)"
               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
        <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 gap-2" x-show="attachmentPreviews.length">
            <template x-for="(src, i) in attachmentPreviews" :key="i">
                <img :src="src" class="w-full aspect-square rounded-md object-cover border border-gray-200">
            </template>
        </div>
    </div>

    {{-- Tanda tangan pemohon (wajib saat Kirim Pengajuan, opsional saat Draft) --}}
    <div class="mt-6">
        <x-signature-pad name="signature" :saved-signature-url="$savedSignatureUrl"
                          label="Tanda Tangan Pemohon *" :show-save-as-default-option="true" />
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('ga.requests.index') }}"
           class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
        <button type="submit" name="intent" value="draft"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
            Simpan Draft
        </button>
        <button type="submit" name="intent" value="submit"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
            Kirim Pengajuan
        </button>
    </div>
</form>
