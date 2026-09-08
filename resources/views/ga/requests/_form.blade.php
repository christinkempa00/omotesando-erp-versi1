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
        discountMode: 'percent',
        discountValue: {{ Illuminate\Support\Js::from($initialDiscountPercent !== null ? (float) $initialDiscountPercent : 0) }},
        pphEnabled: {{ Illuminate\Support\Js::from($initialPphPercent !== null) }},
        pphMode: 'percent',
        pphValue: {{ Illuminate\Support\Js::from($initialPphPercent !== null ? (float) $initialPphPercent : 0) }},
        get subtotal() {
            return this.items.reduce((sum, i) => sum + (Number(i.qty || 0) * Number(i.price_per_unit || 0)), 0);
        },
        get discountAmount() {
            if (!this.discountEnabled) return 0;
            return this.discountMode === 'percent'
                ? this.subtotal * (Number(this.discountValue || 0) / 100)
                : Number(this.discountValue || 0);
        },
        get discountPercentForSubmit() {
            if (this.discountMode === 'percent') return Number(this.discountValue || 0);
            return this.subtotal > 0 ? (Number(this.discountValue || 0) / this.subtotal) * 100 : 0;
        },
        setDiscountMode(mode) {
            if (mode === this.discountMode) return;
            this.discountValue = mode === 'amount'
                ? this.subtotal * (Number(this.discountValue || 0) / 100)
                : (this.subtotal > 0 ? (Number(this.discountValue || 0) / this.subtotal) * 100 : 0);
            this.discountMode = mode;
        },
        get pphAmount() {
            if (!this.pphEnabled) return 0;
            return this.pphMode === 'percent'
                ? this.subtotal * (Number(this.pphValue || 0) / 100)
                : Number(this.pphValue || 0);
        },
        get pphPercentForSubmit() {
            if (this.pphMode === 'percent') return Number(this.pphValue || 0);
            return this.subtotal > 0 ? (Number(this.pphValue || 0) / this.subtotal) * 100 : 0;
        },
        setPphMode(mode) {
            if (mode === this.pphMode) return;
            this.pphValue = mode === 'amount'
                ? this.subtotal * (Number(this.pphValue || 0) / 100)
                : (this.subtotal > 0 ? (Number(this.pphValue || 0) / this.subtotal) * 100 : 0);
            this.pphMode = mode;
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-ink mb-1">Outlet *</label>
            <select name="branch_id" required x-model="branchId" @change="branchLocationId = ''" class="w-full field-input">
                <option value="">-- Pilih Outlet --</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $gaRequest?->branch_id) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Pemohon *</label>
            <input type="text" name="requester_name" required value="{{ old('requester_name', $gaRequest?->requester_name) }}" placeholder="Nama Pemohon"
                   class="w-full field-input">
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Prioritas *</label>
            <select name="priority" required class="w-full field-input">
                @foreach ($priorityLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', $gaRequest?->priority ?? 'normal') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Kategori / Tujuan *</label>
            <select name="category" required class="w-full field-input">
                @foreach ($categoryLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $gaRequest?->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2" x-show="availableBranchLocations.length > 0" x-cloak>
            <label class="block text-sm font-medium text-ink mb-1">Cabang</label>
            <select name="branch_location_id" x-model="branchLocationId" class="w-full field-input">
                <option value="">-- Pilih Cabang --</option>
                <template x-for="loc in availableBranchLocations" :key="loc.id">
                    <option :value="loc.id" x-text="loc.name"></option>
                </template>
            </select>
        </div>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-medium text-ink mb-1">Deskripsi / Keterangan *</label>
        <textarea name="description" rows="3" required
                  class="w-full rounded-lg border-hairline focus:border-action focus:ring-action">{{ old('description', $gaRequest?->description) }}</textarea>
    </div>

    {{-- Item dinamis --}}
    <div class="mt-6">
        <label class="block text-sm font-medium text-ink mb-2">Daftar Item *</label>
        <div class="space-y-3">
            <template x-for="(item, index) in items" :key="index">
                <div class="relative grid grid-cols-12 gap-2 items-center border border-hairline rounded-md p-3 pr-8">
                    <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                            class="absolute top-2 right-2 px-1.5 py-1 text-sm text-red-600 hover:bg-red-50 rounded-md">✕</button>
                    <div class="col-span-12 sm:col-span-3">
                        <input type="text" :name="`items[${index}][item_name]`" x-model="item.item_name"
                               placeholder="Nama item" required
                               class="w-full field-input text-sm">
                    </div>
                    <div class="col-span-12 sm:col-span-1">
                        <input type="text" :name="`items[${index}][type]`" x-model="item.type"
                               placeholder="Tipe"
                               class="w-full field-input text-sm">
                    </div>
                    <div class="col-span-12 sm:col-span-1">
                        <input type="number" min="1" :name="`items[${index}][qty]`" x-model="item.qty"
                               placeholder="Qty" required
                               class="w-full field-input text-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                    </div>
                    <div class="col-span-12 sm:col-span-1">
                        <input type="text" :name="`items[${index}][unit]`" x-model="item.unit"
                               placeholder="Satuan" required
                               class="w-full field-input text-sm">
                    </div>
                    <div class="col-span-12 sm:col-span-2">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm pointer-events-none">Rp</span>
                            <input type="text" inputmode="numeric"
                                   :value="formatThousands(item.price_per_unit)"
                                   @input="item.price_per_unit = parseThousands($event.target.value)"
                                   placeholder="0"
                                   class="w-full field-input text-sm pl-10">
                        </div>
                        <input type="hidden" :name="`items[${index}][price_per_unit]`" :value="item.price_per_unit">
                    </div>
                    <div class="col-span-12 sm:col-span-2">
                        <input type="text" :name="`items[${index}][vendor_name]`" x-model="item.vendor_name"
                               placeholder="Vendor"
                               class="w-full field-input text-sm">
                    </div>
                    <div class="col-span-12 sm:col-span-2">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted text-sm pointer-events-none">Rp</span>
                            <input type="text" readonly tabindex="-1"
                                   :value="formatThousands((item.qty || 0) * (item.price_per_unit || 0))"
                                   class="w-full rounded-md border-hairline bg-surface text-ink-muted shadow-sm text-sm pl-10 cursor-default">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" @click="items.push({ item_name: '', type: '', unit: 'Pcs', qty: 1, price_per_unit: 0, vendor_name: '' })"
                class="mt-3 inline-flex items-center gap-2 btn-secondary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Tambah Item
        </button>

        <div class="mt-4 rounded-lg border border-gold-200 bg-gold-50 p-4 space-y-2">
            <div class="flex items-center justify-between text-sm text-ink-muted">
                <span>Sub Total</span>
                <span class="font-mono font-medium text-ink" x-text="rupiah(subtotal)"></span>
            </div>
            <div class="flex items-center justify-between pt-2 mt-1 border-t border-gold-200">
                <span class="text-sm font-semibold text-ink">Total</span>
                <span class="text-xl font-bold text-gold-600 font-mono" x-text="rupiah(grandTotal)"></span>
            </div>

            <div class="pt-3 mt-1 border-t border-gold-200 space-y-2">
                <template x-if="!discountEnabled">
                    <button type="button" @click="discountEnabled = true"
                            class="inline-flex items-center gap-1.5 text-sm text-gold-600 hover:text-gold-700">
                        + Diskon
                    </button>
                </template>
                <template x-if="discountEnabled">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-sm text-ink-muted flex-wrap gap-2">
                            <span>Diskon</span>
                            <div class="flex items-center gap-2">
                                <div class="inline-flex rounded-md border border-hairline overflow-hidden shrink-0">
                                    <button type="button" @click="setDiscountMode('percent')"
                                            :class="discountMode === 'percent' ? 'bg-action text-white' : 'bg-white text-ink-muted hover:bg-surface'"
                                            class="px-2 h-8 text-xs font-medium">%</button>
                                    <button type="button" @click="setDiscountMode('amount')"
                                            :class="discountMode === 'amount' ? 'bg-action text-white' : 'bg-white text-ink-muted hover:bg-surface'"
                                            class="px-2 h-8 text-xs font-medium border-l border-hairline">Rp</button>
                                </div>
                                <input type="number" x-show="discountMode === 'percent'" min="0" :max="discountMode === 'percent' ? 100 : null" step="0.01" x-model="discountValue"
                                       class="w-16 field-input text-sm text-right">
                                <div class="relative" x-show="discountMode === 'amount'">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-ink-muted text-xs pointer-events-none">Rp</span>
                                    <input type="text" inputmode="numeric"
                                           :value="formatThousands(discountValue)"
                                           @input="discountValue = parseThousands($event.target.value)"
                                           class="w-28 field-input text-sm pl-7 text-right">
                                </div>
                                <span class="font-mono text-ink font-medium w-24 text-right">&minus; <span x-text="rupiah(discountAmount)"></span></span>
                                <button type="button" @click="discountEnabled = false; discountValue = 0; discountMode = 'percent'" class="text-xs text-red-500 hover:text-red-700 shrink-0">Hapus</button>
                            </div>
                        </div>
                        <input type="hidden" name="discount_percent" :value="discountPercentForSubmit">
                    </div>
                </template>

                <template x-if="!pphEnabled">
                    <button type="button" @click="pphEnabled = true"
                            class="inline-flex items-center gap-1.5 text-sm text-gold-600 hover:text-gold-700">
                        + PPH
                    </button>
                </template>
                <template x-if="pphEnabled">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-sm text-ink-muted flex-wrap gap-2">
                            <span>PPH</span>
                            <div class="flex items-center gap-2">
                                <div class="inline-flex rounded-md border border-hairline overflow-hidden shrink-0">
                                    <button type="button" @click="setPphMode('percent')"
                                            :class="pphMode === 'percent' ? 'bg-action text-white' : 'bg-white text-ink-muted hover:bg-surface'"
                                            class="px-2 h-8 text-xs font-medium">%</button>
                                    <button type="button" @click="setPphMode('amount')"
                                            :class="pphMode === 'amount' ? 'bg-action text-white' : 'bg-white text-ink-muted hover:bg-surface'"
                                            class="px-2 h-8 text-xs font-medium border-l border-hairline">Rp</button>
                                </div>
                                <input type="number" x-show="pphMode === 'percent'" min="0" :max="pphMode === 'percent' ? 100 : null" step="0.01" x-model="pphValue"
                                       class="w-16 field-input text-sm text-right">
                                <div class="relative" x-show="pphMode === 'amount'">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-ink-muted text-xs pointer-events-none">Rp</span>
                                    <input type="text" inputmode="numeric"
                                           :value="formatThousands(pphValue)"
                                           @input="pphValue = parseThousands($event.target.value)"
                                           class="w-28 field-input text-sm pl-7 text-right">
                                </div>
                                <span class="font-mono text-ink font-medium w-24 text-right">&minus; <span x-text="rupiah(pphAmount)"></span></span>
                                <button type="button" @click="pphEnabled = false; pphValue = 0; pphMode = 'percent'" class="text-xs text-red-500 hover:text-red-700 shrink-0">Hapus</button>
                            </div>
                        </div>
                        <input type="hidden" name="pph_percent" :value="pphPercentForSubmit">
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Lampiran foto (opsional) --}}
    <div class="mt-6">
        <label class="block text-sm font-medium text-ink mb-1">Lampiran Foto</label>
        <p class="text-xs text-ink-muted mb-2">Opsional, bisa lampirkan lebih dari satu foto.</p>
        @if ($gaRequest && $gaRequest->attachments->isNotEmpty())
            <div class="mb-3 grid grid-cols-4 sm:grid-cols-6 gap-2">
                @foreach ($gaRequest->attachments as $attachment)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-md object-cover border border-hairline">
                @endforeach
            </div>
            <p class="text-xs text-ink-muted mb-2">Foto di atas sudah tersimpan. Unggah di bawah kalau mau menambah lagi.</p>
        @endif
        <input type="file" name="attachments[]" multiple accept="image/*" @change="onAttachmentsChange($event)"
               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-gold-50 file:text-gold-700 hover:file:bg-gold-100">
        <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 gap-2" x-show="attachmentPreviews.length">
            <template x-for="(src, i) in attachmentPreviews" :key="i">
                <img :src="src" class="w-full aspect-square rounded-md object-cover border border-hairline">
            </template>
        </div>
    </div>

    {{-- Tanda tangan pemohon (wajib saat Kirim Pengajuan, opsional saat Draft) --}}
    <div class="mt-6">
        <x-signature-pad name="signature" :saved-signature-url="$savedSignatureUrl"
                          label="Tanda Tangan Pemohon *" :show-save-as-default-option="true" />
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route(str_starts_with(request()->route()->getName(), 'outlet.') ? 'outlet.requests.index' : 'ga.requests.index') }}"
           class="btn-secondary">Batal</a>
        <button type="submit" name="intent" value="draft"
                class="inline-flex items-center btn-secondary">
            Draft
        </button>
        <button type="submit" name="intent" value="submit"
                class="inline-flex items-center btn-primary">
            Kirim Pengajuan
        </button>
    </div>
</form>
