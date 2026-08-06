<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.uniforms.stocks.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Varian Seragam</h2>
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

                <form method="POST" action="{{ route('ga.uniforms.stocks.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Seragam *</label>
                            <input type="text" name="uniform_type" required value="{{ old('uniform_type', $prefill['uniform_type']) }}"
                                   placeholder="mis. Vest, Kemeja, Celana"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                            <select name="branch_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Outlet --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $prefill['branch_id']) == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Warna</label>
                            <input type="text" name="color" value="{{ old('color', $prefill['color']) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi *</label>
                            <select name="status" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'bagus') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ambang Low Stock</label>
                            <input type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', 0) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">Berlaku sama untuk semua ukuran di bawah.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Varian</label>
                            <input type="file" name="stock_photo" accept="image/*"
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                    </div>

                    <div class="mt-6 border border-gray-200 rounded-lg p-4"
                         x-data="{
                             rows: {{ Illuminate\Support\Js::from(
                                 old('sizes') ?: collect($sizes)->map(fn ($size) => ['name' => $size, 'qty' => 0])->values()->all()
                             ) }},
                             addRow() { this.rows.push({ name: '', qty: 0 }); },
                         }">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Jumlah Stok per Ukuran *</label>
                            <button type="button" @click="addRow()" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                + Tambah Ukuran
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mb-3">Isi ukuran &amp; jumlah stoknya — kosongkan/hapus baris kalau ukuran itu tidak ada. Bisa tambah ukuran apa saja.</p>

                        <div class="space-y-2">
                            <template x-for="(row, index) in rows" :key="index">
                                <div class="flex items-center gap-2">
                                    <input type="text" :name="`sizes[${index}][name]`" x-model="row.name" placeholder="Ukuran"
                                           class="w-32 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <input type="number" min="0" :name="`sizes[${index}][qty]`" x-model="row.qty" placeholder="Jumlah stok"
                                           class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <button type="button" @click="rows.splice(index, 1)" x-show="rows.length > 1"
                                            class="text-gray-400 hover:text-red-600 shrink-0">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
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
