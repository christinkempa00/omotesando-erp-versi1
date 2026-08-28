@php
    $job = $job ?? null;
    $existingChecklist = old('checklist', collect($job->checklist ?? [])->pluck('text')->all());
    if (empty($existingChecklist)) {
        $existingChecklist = [''];
    }
    $assetsData = $assets->map(fn ($asset) => [
        'id' => $asset->id,
        'code' => $asset->asset_code,
        'name' => $asset->name,
        'branch_id' => $asset->branch_id,
        'location' => $asset->location,
    ])->values();
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5"
     x-data="{
        branchId: '{{ old('branch_id', $job->branch_id ?? '') }}',
        branchLocationId: '{{ old('branch_location_id', $job->branch_location_id ?? '') }}',
        branchLocations: @js($branchLocations),
        get availableBranchLocations() { return this.branchLocations[this.branchId] || []; },
        location: '{{ old('location', $job->location ?? '') }}',
        assetId: '{{ old('asset_id', $job->asset_id ?? ($selectedAssetId ?? '')) }}',
        assets: {{ Illuminate\Support\Js::from($assetsData) }},
        cost: {{ (float) old('cost', $job->cost ?? 0) }},
        scheduledDate: '{{ old('scheduled_date', optional($job->scheduled_date ?? null)->format('Y-m-d')) }}',
        isCreate: {{ $job ? 'false' : 'true' }},
        get minTime() {
            const d = new Date();
            const todayStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            if (! this.isCreate || this.scheduledDate !== todayStr) {
                return '';
            }
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        },
        get filteredAssets() {
            return this.assets.filter(a =>
                (!this.branchId || String(a.branch_id) === String(this.branchId)) &&
                (!this.location || a.location === this.location)
            );
        },
        onFilterChange() {
            if (! this.filteredAssets.some(a => String(a.id) === String(this.assetId))) {
                this.assetId = '';
            }
            if (! this.availableBranchLocations.some(l => String(l.id) === String(this.branchLocationId))) {
                this.branchLocationId = '';
            }
        },
        formatThousands(n) { return (Number(n) || 0).toLocaleString('id-ID'); },
        parseThousands(str) { return Number(String(str).replace(/[^\d]/g, '')) || 0; }
     }">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Pekerjaan *</label>
        <input type="text" name="title" required value="{{ old('title', $job->title ?? '') }}"
               placeholder="Contoh: Servis rutin AC bulanan"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
        <select name="branch_id" x-model="branchId" @change="onFilterChange()" required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Outlet --</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>

    <div x-show="availableBranchLocations.length > 0" x-cloak>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
        <select name="branch_location_id" x-model="branchLocationId"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Cabang --</option>
            <template x-for="loc in availableBranchLocations" :key="loc.id">
                <option :value="loc.id" x-text="loc.name"></option>
            </template>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi *</label>
        <select name="location" x-model="location" @change="onFilterChange()" required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Lokasi --</option>
            @foreach (['Kitchen', 'Floor', 'Bar', 'Lainnya'] as $loc)
                <option value="{{ $loc }}">{{ $loc }}</option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Aset *</label>
        <select name="asset_id" x-model="assetId" required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Aset --</option>
            <template x-for="asset in filteredAssets" :key="asset.id">
                <option :value="asset.id" x-text="asset.code + ' — ' + asset.name"></option>
            </template>
        </select>
        <p class="text-xs text-gray-400 mt-1" x-show="branchId || location">
            Menampilkan aset sesuai outlet/lokasi yang dipilih.
        </p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Pekerjaan *</label>
        <select name="type" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $job->type ?? 'preventive') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas *</label>
        <select name="priority" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($priorityLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $job->priority ?? 'normal') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
        <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $job->status ?? 'scheduled') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Jadwal *</label>
        <input type="date" name="scheduled_date" x-model="scheduledDate" required
               @if (! $job) min="{{ now()->toDateString() }}" @endif
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Jam Mulai *</label>
        <input type="time" name="scheduled_time" required
               value="{{ old('scheduled_time', $job->scheduled_time ?? '') }}"
               :min="minTime"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
        <input type="time" name="scheduled_end_time"
               value="{{ old('scheduled_end_time', $job->scheduled_end_time ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Penanggung Jawab (PIC) *</label>
        <input type="text" name="pic_name" required value="{{ old('pic_name', $job->pic_name ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
        <input type="text" name="vendor_name" value="{{ old('vendor_name', $job->vendor_name ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Biaya</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">Rp</span>
            <input type="text" inputmode="numeric"
                   :value="formatThousands(cost)"
                   @input="cost = parseThousands($event.target.value)"
                   placeholder="0"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10">
        </div>
        <input type="hidden" name="cost" :value="cost">
    </div>

    {{-- Checklist dinamis --}}
    <div class="sm:col-span-2" x-data="{ items: {{ Illuminate\Support\Js::from($existingChecklist) }} }">
        <label class="block text-sm font-medium text-gray-700 mb-1">Checklist Pekerjaan</label>
        <div class="space-y-2">
            <template x-for="(item, index) in items" :key="index">
                <div class="flex items-center gap-2">
                    <input type="text" :name="`checklist[${index}]`" x-model="items[index]"
                           placeholder="Langkah pekerjaan..."
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" @click="items.splice(index, 1)"
                            class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md">Hapus</button>
                </div>
            </template>
        </div>
        <button type="button" @click="items.push('')"
                class="mt-2 inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
            Buat item checklist
        </button>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
        <textarea name="notes" rows="3"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $job->notes ?? '') }}</textarea>
    </div>
</div>
