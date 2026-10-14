@props([
    'name' => 'branch_location_id',
    'selected' => null,
    'label' => 'Cabang',
    'required' => false,
    'storeRoute' => 'ga.branch-locations.store',
    'emitEvent' => null,
    'outletListenEvent' => null,
    'initialBranchId' => null,
])

{{--
    Dropdown "Cabang" (branch_location) — SELALU tampil (tidak lagi
    disembunyikan total spt perilaku lama), tapi ISINYA tetap mengikuti
    Outlet yang sedang dipilih di form yang sama (keputusan CC, 28/09/2026):
    pilih outlet tanpa cabang terdaftar (mis. Zodiac) -> daftar kosong;
    pilih outlet yang py cabang (mis. Ask for Patty) -> muncul Gandaria City
    dst. Quick-add: cabang baru otomatis ikut Outlet yang sedang dipilih,
    tanpa perlu pilih ulang -- lihat BranchLocationQuickCreateController.

    Form pemanggil WAJIB menyiarkan event {{ $outletListenEvent ?? '(nama event)' }}
    (detail: {branchId}) tiap kali field Outlet-nya berubah, supaya daftar
    Cabang di sini ikut ter-filter ulang.
--}}
@php
    $locations = \App\Models\BranchLocation::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'branch_id']);
@endphp

<div x-data="branchLocationSelect({
        selected: {{ $selected ? "'".$selected."'" : 'null' }},
        locations: @js($locations),
        storeUrl: @js(route($storeRoute)),
        emitEvent: @js($emitEvent),
        currentOutletId: {{ $initialBranchId ? "'".$initialBranchId."'" : 'null' }},
     })"
     @if ($outletListenEvent) x-on:{{ $outletListenEvent }}.window="handleOutletChanged($event.detail.branchId)" @endif>
    <label class="block text-sm font-medium text-ink mb-1">{{ $label }}{!! $required ? ' <span class="text-red-500">*</span>' : '' !!}</label>
    <select name="{{ $name }}" x-model="selected" {{ $required ? 'required' : '' }}
            class="w-full field-input">
        <option value="">-- Pilih Cabang --</option>
        <template x-for="loc in availableLocations" :key="loc.id">
            <option :value="String(loc.id)" x-text="loc.name"></option>
        </template>
        <option value="__new__" :disabled="!currentOutletId">+ Tambah Cabang Baru...</option>
    </select>
    <p x-show="!currentOutletId" x-cloak class="mt-1 text-xs text-ink-muted">Pilih Outlet dulu untuk melihat/menambah Cabang.</p>

    <div x-show="adding" x-cloak class="mt-2 space-y-2 border border-hairline rounded-lg p-3 bg-surface">
        <div>
            <label class="block text-xs text-ink-muted mb-1">Nama Cabang</label>
            <input type="text" x-model="newName" placeholder="Contoh: Senayan City" class="w-full field-input text-sm">
        </div>
        <p x-show="error" x-cloak x-text="error" class="text-xs text-red-500"></p>
        <div class="flex items-center gap-2">
            <button type="button" @click="create()" :disabled="creating || !newName.trim()"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-action text-white hover:bg-action-hover disabled:opacity-40">
                <span x-text="creating ? 'Menambah...' : 'Tambah'"></span>
            </button>
            <button type="button" @click="cancelAdd()" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-hairline text-ink-muted hover:bg-white">
                Batal
            </button>
        </div>
    </div>
</div>

@once
    <script>
        function branchLocationSelect({ selected, locations, storeUrl, emitEvent, currentOutletId }) {
            return {
                selected: selected,
                locations: locations,
                storeUrl: storeUrl,
                emitEvent: emitEvent,
                currentOutletId: currentOutletId,
                adding: false,
                newName: '',
                creating: false,
                error: '',
                get availableLocations() {
                    if (!this.currentOutletId) return [];

                    return this.locations.filter((l) => String(l.branch_id) === String(this.currentOutletId));
                },
                init() {
                    // $watch (bukan @change) supaya perubahan dari create()
                    // (auto-select cabang baru) juga ikut memicu event ini,
                    // tidak cuma interaksi langsung user di <select>.
                    this.$watch('selected', (value) => {
                        if (value === '__new__') {
                            this.adding = true;
                            this.selected = '';
                            return;
                        }

                        if (this.emitEvent) {
                            const loc = this.locations.find((l) => String(l.id) === String(value));
                            window.dispatchEvent(new CustomEvent(this.emitEvent, {
                                detail: { name: loc ? loc.name : null },
                            }));
                        }
                    });
                },
                // Dipanggil form pemanggil tiap kali field Outlet berubah —
                // reset pilihan Cabang lama (mungkin sudah tidak relevan utk
                // outlet baru) & tutup form tambah yg sedang terbuka.
                handleOutletChanged(branchId) {
                    this.currentOutletId = branchId || null;
                    this.selected = '';
                    this.cancelAdd();
                },
                cancelAdd() {
                    this.adding = false;
                    this.newName = '';
                    this.error = '';
                },
                async create() {
                    const name = this.newName.trim();
                    if (!name || !this.currentOutletId) return;

                    this.creating = true;
                    this.error = '';

                    try {
                        const res = await fetch(this.storeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ name, branch_id: this.currentOutletId }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            throw new Error(data.message ?? (data.errors ? Object.values(data.errors)[0][0] : 'Gagal menambah cabang.'));
                        }

                        this.locations.push({ id: data.id, name: data.name, branch_id: data.branch_id });
                        this.selected = String(data.id);
                        this.cancelAdd();
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.creating = false;
                    }
                },
            };
        }
    </script>
@endonce
