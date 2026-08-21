<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('scm.deliveries.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Buat Surat Jalan
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-md mx-auto sm:px-6">
            @if ($errors->any())
                <div class="mb-5 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $hasAnyStock)
                <div class="bg-white shadow-sm rounded-lg p-6 text-center text-sm text-gray-500">
                    Belum ada stok batch yang tersedia untuk dikirim.
                    <a href="{{ route('scm.batches.index') }}" class="text-accent hover:underline">Lihat Batch Produksi</a>.
                </div>
            @else
                <form method="POST" action="{{ route('scm.deliveries.store') }}" enctype="multipart/form-data"
                      x-data="{
                        step: 1,
                        totalSteps: 4,
                        isAdmin: @js($isAdmin),
                        toBranchId: '{{ old('to_branch_id') }}',
                        fromBranchId: '{{ old('from_branch_id', $defaultFromBranchId) }}',
                        selected: {},
                        labels: [],
                        loadingLabels: false,
                        hasPhoto: false,
                        async fetchLabels() {
                            if (! this.fromBranchId) { this.labels = []; return; }
                            this.loadingLabels = true;
                            this.selected = {};
                            try {
                                const res = await fetch(`{{ route('scm.deliveries.available-labels') }}?from_branch_id=${this.fromBranchId}`, {
                                    headers: { 'Accept': 'application/json' },
                                });
                                const data = await res.json();
                                this.labels = data.labels;
                            } finally {
                                this.loadingLabels = false;
                            }
                        },
                        toggleLabel(id) {
                            if (this.selected[id] !== undefined) {
                                const next = { ...this.selected };
                                delete next[id];
                                this.selected = next;
                            } else {
                                this.selected = { ...this.selected, [id]: 1 };
                            }
                        },
                        adjustQty(id, delta, max) {
                            const current = this.selected[id] ?? 1;
                            const next = Math.max(1, Math.min(max, current + delta));
                            this.selected = { ...this.selected, [id]: next };
                        },
                        onPhotoChange(e) {
                            this.hasPhoto = e.target.files.length > 0;
                        },
                        get selectedCount() { return Object.keys(this.selected).length; },
                        get canProceed() {
                            if (this.step === 1) return this.toBranchId && (!this.isAdmin || this.fromBranchId);
                            if (this.step === 2) return this.selectedCount > 0;
                            if (this.step === 3) return this.hasPhoto;
                            return true;
                        },
                        next() { if (this.canProceed && this.step < this.totalSteps) this.step++; },
                        back() { if (this.step > 1) this.step--; },
                      }"
                      x-init="if (fromBranchId) fetchLabels(); $watch('fromBranchId', () => fetchLabels());"
                >
                    @csrf
                    <input type="hidden" name="to_branch_id" :value="toBranchId">
                    <input type="hidden" name="from_branch_id" :value="fromBranchId">
                    <template x-for="([id, qty], idx) in Object.entries(selected)" :key="id">
                        <span>
                            <input type="hidden" :name="`items[${idx}][batch_label_id]`" :value="id">
                            <input type="hidden" :name="`items[${idx}][qty_sent]`" :value="qty">
                        </span>
                    </template>

                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        {{-- Header progres --}}
                        <div class="px-5 pt-4 pb-3 border-b border-hairline">
                            <div class="text-[11px] font-bold text-accent uppercase tracking-wide">Surat Jalan Baru</div>
                            <div class="text-base font-bold text-ink mt-0.5" x-text="'Langkah ' + step + ' dari ' + totalSteps"></div>
                            <div class="flex gap-1 mt-2.5">
                                <template x-for="n in totalSteps" :key="n">
                                    <div class="flex-1 h-1 rounded-full" :class="n <= step ? 'bg-accent' : 'bg-hairline'"></div>
                                </template>
                            </div>
                        </div>

                        <div class="p-5 min-h-[320px]">
                            {{-- Step 1: Outlet tujuan --}}
                            <div x-show="step === 1" x-cloak>
                                @if ($isAdmin)
                                    <label class="block text-xs font-semibold text-ink-muted mb-1.5">Dari (Gudang/Central)</label>
                                    <select x-model="fromBranchId" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent mb-4">
                                        <option value="">-- Pilih Asal --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <div class="text-sm font-bold text-ink mb-3">Pilih outlet tujuan</div>
                                <div class="space-y-2">
                                    @foreach ($branches as $branch)
                                        <div @click="toBranchId = '{{ $branch->id }}'"
                                             class="flex items-center justify-between px-4 py-3.5 rounded-xl border-[1.5px] cursor-pointer transition"
                                             :class="toBranchId === '{{ $branch->id }}' ? 'border-accent bg-accent-tint' : 'border-hairline bg-white'">
                                            <span class="text-sm font-semibold" :class="toBranchId === '{{ $branch->id }}' ? 'text-accent' : 'text-ink'">{{ $branch->name }}</span>
                                            <span x-show="toBranchId === '{{ $branch->id }}'" class="text-accent font-extrabold">✓</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Step 2: Item / batch (FEFO — diurutkan server dari yang paling dekat ED) --}}
                            <div x-show="step === 2" x-cloak>
                                <div class="text-sm font-bold text-ink mb-1">Pilih batch produksi yang dikirim</div>
                                <p class="text-xs text-ink-muted mb-3">Diurutkan otomatis berdasarkan tanggal kedaluwarsa terdekat (FEFO). Ketuk item untuk pilih, atur qty dengan +/−.</p>

                                <div x-show="loadingLabels" x-cloak class="text-center text-xs text-ink-muted py-6">Memuat stok tersedia…</div>
                                <div x-show="!loadingLabels && labels.length === 0" x-cloak class="text-center text-xs text-ink-muted py-6">
                                    Tidak ada stok tersedia di lokasi asal ini.
                                </div>

                                <div class="space-y-2" x-show="!loadingLabels">
                                    <template x-for="label in labels" :key="label.id">
                                        <div class="rounded-xl border p-3"
                                             :class="selected[label.id] !== undefined ? 'border-accent bg-accent-tint' : 'border-hairline bg-white'">
                                            <div class="flex items-center gap-3" @click="toggleLabel(label.id)" style="cursor:pointer">
                                                <div class="w-5 h-5 rounded-md flex items-center justify-center shrink-0 text-white text-xs"
                                                     :class="selected[label.id] !== undefined ? 'bg-accent' : 'bg-gray-200'">
                                                    <span x-show="selected[label.id] !== undefined">✓</span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-semibold text-ink truncate" x-text="label.item_name"></div>
                                                    <div class="text-xs text-ink-muted font-mono">
                                                        <span x-text="label.label_code"></span> · maks <span x-text="label.qty_available"></span> <span x-text="label.unit"></span>
                                                    </div>
                                                    <div class="text-xs font-semibold mt-0.5" x-show="label.expiry_date"
                                                         :class="label.is_near_expiry ? 'text-status-discrepancy-fg' : 'text-ink-muted'">
                                                        ED <span x-text="label.expiry_date"></span>
                                                        <span x-show="label.is_near_expiry"> · sisa <span x-text="label.days_until_expiry"></span> hari</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3 mt-3" x-show="selected[label.id] !== undefined" x-cloak>
                                                <button type="button" @click.stop="adjustQty(label.id, -1, label.qty_available)"
                                                        class="w-9 h-9 rounded-lg bg-white border border-hairline flex items-center justify-center text-lg font-bold text-ink">−</button>
                                                <div class="flex-1 text-center text-base font-extrabold font-mono" x-text="selected[label.id] ?? 1"></div>
                                                <button type="button" @click.stop="adjustQty(label.id, 1, label.qty_available)"
                                                        class="w-9 h-9 rounded-lg bg-white border border-hairline flex items-center justify-center text-lg font-bold text-ink">+</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Step 3: Foto bukti muat --}}
                            <div x-show="step === 3" x-cloak>
                                <div class="text-sm font-bold text-ink mb-1">Foto bukti muat barang</div>
                                <p class="text-xs text-ink-muted mb-3.5">Wajib diisi sebelum surat jalan bisa dikirim</p>

                                <input type="file" name="sent_photo" accept="image/*" capture="environment" required
                                       @change="onPhotoChange"
                                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <p class="text-xs text-ink-muted mt-1">Wajib — foto bukti muat barang sebelum surat jalan bisa dikirim.</p>
                            </div>

                            {{-- Step 4: Review --}}
                            <div x-show="step === 4" x-cloak>
                                <div class="text-sm font-bold text-ink mb-3">Review sebelum kirim</div>
                                <div class="bg-surface rounded-xl p-3.5 space-y-2.5 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-ink-muted">Outlet tujuan</span>
                                        <span class="font-bold text-ink">
                                            @foreach ($branches as $branch)
                                                <span x-show="toBranchId === '{{ $branch->id }}'">{{ $branch->name }}</span>
                                            @endforeach
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-ink-muted">Jumlah item</span>
                                        <span class="font-bold font-mono text-ink" x-text="selectedCount + ' batch'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-ink-muted">Foto bukti muat</span>
                                        <span class="font-bold text-status-approved-fg">✓ Terlampir</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border-t border-hairline flex gap-2.5">
                            <button type="button" x-show="step > 1" @click="back()"
                                    class="px-5 h-[52px] rounded-2xl border border-hairline text-ink font-bold text-sm">
                                Kembali
                            </button>
                            <button type="button" x-show="step < totalSteps" @click="next()" :disabled="! canProceed"
                                    class="flex-1 h-[52px] rounded-2xl bg-accent text-white font-bold text-[15px] disabled:opacity-40">
                                Lanjut
                            </button>
                            <button type="submit" x-show="step === totalSteps"
                                    class="flex-1 h-[52px] rounded-2xl bg-accent text-white font-bold text-[15px]">
                                Kirim Surat Jalan
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
