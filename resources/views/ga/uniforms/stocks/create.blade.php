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

                <form method="POST" action="{{ route('ga.uniforms.stocks.store') }}" enctype="multipart/form-data"
                      x-data="{
                          uniformType: {{ Illuminate\Support\Js::from(old('uniform_type', $prefill['uniform_type'] ?? '')) }},
                          branchId: {{ Illuminate\Support\Js::from((string) old('branch_id', $prefill['branch_id'] ?? '')) }},
                          typesByBranch: {{ Illuminate\Support\Js::from($typesByBranch) }},
                          get isDuplicateType() {
                              if (! this.uniformType || ! this.branchId) return false;
                              const typed = this.uniformType.trim().toLowerCase();
                              const list = this.typesByBranch[this.branchId] || [];
                              return list.some(t => t.trim().toLowerCase() === typed);
                          }
                      }">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Seragam *</label>
                            <input type="text" name="uniform_type" required x-model="uniformType"
                                   placeholder="mis. Vest, Kemeja, Celana"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                            <select name="branch_id" required @change="branchId = $event.target.value" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Outlet --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $prefill['branch_id']) == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Warna *</label>
                            <input type="text" name="color" required value="{{ old('color', $prefill['color']) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ambang Low Stock</label>
                            <input type="number" min="0" name="low_stock_threshold" value="{{ old('low_stock_threshold', 0) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">Berlaku sama untuk semua ukuran di bawah.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Varian</label>
                            @if ($currentPhotoPath)
                                <div class="flex items-center gap-3 mb-2">
                                    <img src="{{ Storage::url($currentPhotoPath) }}" class="w-14 h-14 rounded-md object-cover border border-gray-200">
                                    <p class="text-xs text-gray-400">Foto saat ini. Pilih file baru di bawah untuk menggantinya.</p>
                                </div>
                            @endif
                            <input type="file" name="stock_photo" accept="image/*"
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                    </div>

                    @if ($existingStock->isEmpty())
                        <div class="mt-3 flex items-start gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700"
                             x-show="isDuplicateType" x-cloak>
                            <span><strong>Info (bukan error, form tetap bisa disimpan):</strong> nama "<span x-text="uniformType" class="font-semibold"></span>" sudah dipakai di outlet ini. Kalau memang mau menambah varian baru, cek dulu apakah maksudnya menambah ukuran ke varian yang sudah ada (pakai ikon + pada kartu varian tersebut di halaman daftar) — kalau bukan, ganti nama Tipe Seragam supaya tidak tertukar dengan varian lain.</span>
                        </div>
                    @endif

                    @if ($existingStock->isNotEmpty())
                        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-700 mb-1">Sisa Stok Saat Ini</p>
                            <p class="text-xs text-gray-400 mb-3">Tampilan saja, tidak bisa diedit di sini — jumlah yang diisi di bawah akan DITAMBAHKAN ke stok yang sudah ada.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach ($existingStock as $s)
                                    <div class="flex items-center justify-between bg-white border border-gray-200 rounded-md px-3 py-2">
                                        <span class="text-xs text-gray-500">Size {{ $s->size ?: '-' }}</span>
                                        <span class="text-sm font-semibold text-gray-800">{{ $s->available_stock }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

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
                                Buat Ukuran
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
