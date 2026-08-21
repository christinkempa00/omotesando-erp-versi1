<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.uniforms.stocks.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Grup Varian — {{ $first->uniform_type }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ga.uniforms.stocks.update-group') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="old_uniform_type" value="{{ $first->uniform_type }}">
                    <input type="hidden" name="old_branch_id" value="{{ $first->branch_id }}">
                    <input type="hidden" name="old_color" value="{{ $first->color }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Seragam *</label>
                            <input type="text" name="uniform_type" required value="{{ old('uniform_type', $first->uniform_type) }}"
                                   placeholder="mis. Vest, Kemeja, Celana"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                            <select name="branch_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Outlet --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $first->branch_id) == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Warna *</label>
                            <input type="text" name="color" required value="{{ old('color', $first->color) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ambang Low Stock</label>
                            <input type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', $first->low_stock_threshold) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">Berlaku sama untuk semua ukuran di grup ini.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Varian</label>
                            @if ($first->stock_photo_path)
                                <img src="{{ Storage::url($first->stock_photo_path) }}" class="w-24 h-24 rounded object-cover mb-2">
                            @endif
                            <input type="file" name="stock_photo" accept="image/*"
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                    </div>

                    <div class="mt-6 border border-gray-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Ukuran dalam Grup Ini</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach ($items as $item)
                                <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                                    <span class="text-xs text-gray-500">Size {{ $item->size ?: '-' }}</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ $item->available_stock }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Tampilan saja — ukuran &amp; jumlah stok tidak bisa diubah di sini.</p>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('ga.uniforms.stocks.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
