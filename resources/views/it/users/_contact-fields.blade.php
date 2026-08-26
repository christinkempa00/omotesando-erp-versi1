@php
    $user = $user ?? null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="text-red-500">*</span></label>
        <input type="text" name="name" required value="{{ old('name', $user->name ?? '') }}"
               class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
        <input type="email" name="email" required value="{{ old('email', $user->email ?? '') }}"
               autocomplete="off"
               class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Divisi</label>
        <select name="division_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
            <option value="">-- Tidak ada --</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->id }}" @selected(old('division_id', $user->division_id ?? '') == $division->id)>{{ $division->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-400">Relevan untuk role yang butuh divisi, mis. Produksi.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Branch / Outlet</label>
        <select name="branch_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
            <option value="">-- Tidak ada --</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $user->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-400">Cuma relevan untuk role yang terikat 1 branch, mis. Gudang/Outlet.</p>
    </div>
</div>
