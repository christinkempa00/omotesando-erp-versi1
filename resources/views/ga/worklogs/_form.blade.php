@php
    $workLog = $workLog ?? null;
@endphp

<div class="bg-white shadow-sm rounded-lg p-6 space-y-6">
    <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Detail Singkat</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5"
             x-data="{
                branchId: '{{ old('branch_id', $workLog->branch_id ?? '') }}',
                branchLocationId: '{{ old('branch_location_id', $workLog->branch_location_id ?? '') }}',
                branchLocations: @js($branchLocations),
                get availableBranchLocations() { return this.branchLocations[this.branchId] || []; },
             }">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pengerjaan *</label>
                <input type="date" name="work_date" required
                       value="{{ old('work_date', optional($workLog?->work_date)->format('Y-m-d')) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div x-data="{
                    start: '{{ old('start_time', $workLog?->start_time ? substr($workLog->start_time, 0, 5) : '') }}',
                    end: '{{ old('end_time', $workLog?->end_time ? substr($workLog->end_time, 0, 5) : '') }}',
                    get duration() {
                        if (! this.start || ! this.end) return '';
                        const [sh, sm] = this.start.split(':').map(Number);
                        const [eh, em] = this.end.split(':').map(Number);
                        const mins = (eh * 60 + em) - (sh * 60 + sm);
                        if (mins <= 0) return '';
                        const h = Math.floor(mins / 60), m = mins % 60;
                        if (h > 0 && m > 0) return h + ' jam ' + m + ' menit';
                        if (h > 0) return h + ' jam';
                        return m + ' menit';
                    }
                 }" class="contents">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai *</label>
                    <input type="time" name="start_time" required x-model="start"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Berakhir *</label>
                    <input type="time" name="end_time" required x-model="end"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1" x-show="duration" x-text="'Durasi: ' + duration"></p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Pengerjaan *</label>
                <select name="category" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($categoryLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', $workLog->category ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                <select name="branch_id" required x-model="branchId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Pilih Outlet --</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $workLog->branch_id ?? '') == $branch->id)>
                            {{ $branch->name }}
                        </option>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Teknisi in Charge *</label>
                <select name="technician_in_charge" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">-- Pilih Teknisi --</option>
                    @foreach ($technicianOptions as $option)
                        <option value="{{ $option }}" @selected(old('technician_in_charge', $workLog->technician_in_charge ?? '') === $option)>{{ $option }}</option>
                    @endforeach
                    @if ($workLog?->technician_in_charge && ! in_array($workLog->technician_in_charge, $technicianOptions, true))
                        <option value="{{ $workLog->technician_in_charge }}" @selected(old('technician_in_charge') === null)>{{ $workLog->technician_in_charge }} (data lama)</option>
                    @endif
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teknisi Assist</label>
                <input type="text" name="technician_assist" placeholder="Nama teknisi pendamping (opsional)"
                       value="{{ old('technician_assist', $workLog->technician_assist ?? '') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <div class="border-t border-gray-100 pt-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Detail Lengkap</h3>
        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Detail Pengerjaan *</label>
                <textarea name="work_detail" rows="4" required placeholder="Jelaskan pekerjaan yang dilakukan"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('work_detail', $workLog->work_detail ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasil Pengerjaan</label>
                <p class="text-xs text-gray-400 mb-1">Boleh dikosongkan dulu — isi begitu pekerjaan selesai dikerjakan.</p>
                <textarea name="work_result" rows="4" placeholder="Jelaskan hasil/kondisi setelah pengerjaan"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('work_result', $workLog->work_result ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="notes" rows="3" placeholder="Catatan tambahan (opsional)"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $workLog->notes ?? '') }}</textarea>
            </div>

            <div x-data="{ previews: [] }">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    @if ($workLog?->attachments->isNotEmpty())
                        Tambah Foto
                    @else
                        Lampiran (Foto)
                    @endif
                </label>
                <p class="text-xs text-gray-400 mb-2">Boleh lebih dari satu foto.</p>

                {{-- Foto yang sudah ada (kalau edit) ditampilkan TERPISAH di luar
                     form ini, lihat edit.blade.php — form hapus per-foto tidak
                     boleh nested di dalam form utama (browser akan memotong
                     form induk di form pertama yang ditemui). --}}

                <input type="file" name="attachments[]" multiple accept="image/*" @change="previews = Array.from($event.target.files).map(f => URL.createObjectURL(f))"
                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 gap-2" x-show="previews.length">
                    <template x-for="(src, i) in previews" :key="i">
                        <img :src="src" class="w-full aspect-square rounded-md object-cover border border-gray-200">
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
