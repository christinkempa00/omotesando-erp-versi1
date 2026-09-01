@php
    $stock = $stock ?? null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5"
     x-data="{
        branchId: '{{ old('branch_id', $stock->branch_id ?? '') }}',
        branchLocationId: '{{ old('branch_location_id', $stock->branch_location_id ?? '') }}',
        branchLocations: @js($branchLocations),
        get availableBranchLocations() { return this.branchLocations[this.branchId] || []; },
     }">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Seragam *</label>
        <input type="text" name="uniform_type" required value="{{ old('uniform_type', $stock->uniform_type ?? '') }}"
               placeholder="mis. Vest, Kemeja, Celana"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
        <select name="branch_id" required x-model="branchId" @change="branchLocationId = ''" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Pilih Outlet --</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $stock->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Ukuran</label>
        <input type="text" name="size" value="{{ old('size', $stock->size ?? '') }}"
               placeholder="S / M / L / XL / XXL"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Warna *</label>
        <input type="text" name="color" required value="{{ old('color', $stock->color ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi *</label>
        <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (\App\Models\GA\UniformStock::statusLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $stock->status ?? 'bagus') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ambang Low Stock</label>
        <input type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', $stock->low_stock_threshold ?? 0) }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-xs text-gray-400 mt-1">Ditandai "low stock" kalau stok tersedia &le; angka ini.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Foto Varian</label>
        @if (! empty($stock?->stock_photo_path))
            <img src="{{ Storage::url($stock->stock_photo_path) }}" class="w-24 h-24 rounded object-cover mb-2">
        @endif
        <input type="file" name="stock_photo" accept="image/*"
               class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
    </div>
</div>

@unless ($stock)
    <p class="text-xs text-gray-400 mt-4">
        Stok awal (tersedia/rusak) dimulai dari 0. Gunakan aksi "Restock" di halaman detail setelah varian ini dibuat.
    </p>
@endunless
