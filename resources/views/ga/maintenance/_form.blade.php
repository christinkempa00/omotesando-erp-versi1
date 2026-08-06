@php
    $job = $job ?? null;
    $existingChecklist = old('checklist', collect($job->checklist ?? [])->pluck('text')->all());
    if (empty($existingChecklist)) {
        $existingChecklist = [''];
    }
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Pekerjaan *</label>
        <input type="text" name="title" required value="{{ old('title', $job->title ?? '') }}"
               placeholder="mis. Servis AC rutin bulanan"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Aset *</label>
        <select name="asset_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Aset --</option>
            @foreach ($assets as $asset)
                <option value="{{ $asset->id }}"
                    @selected(old('asset_id', $job->asset_id ?? ($selectedAssetId ?? '')) == $asset->id)>
                    {{ $asset->asset_code }} — {{ $asset->name }}
                </option>
            @endforeach
        </select>
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
        <input type="date" name="scheduled_date" required
               value="{{ old('scheduled_date', optional($job->scheduled_date ?? null)->format('Y-m-d')) }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Jam</label>
        <input type="time" name="scheduled_time"
               value="{{ old('scheduled_time', $job->scheduled_time ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Penanggung Jawab (PIC)</label>
        <input type="text" name="pic_name" value="{{ old('pic_name', $job->pic_name ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
        <input type="text" name="vendor_name" value="{{ old('vendor_name', $job->vendor_name ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
        <select name="branch_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Outlet --</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $job->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
        <input type="text" name="location" value="{{ old('location', $job->location ?? '') }}"
               placeholder="mis. Dapur, Ruang GM"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Biaya</label>
        <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $job->cost ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
            + Tambah item checklist
        </button>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
        <textarea name="notes" rows="3"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $job->notes ?? '') }}</textarea>
    </div>
</div>
