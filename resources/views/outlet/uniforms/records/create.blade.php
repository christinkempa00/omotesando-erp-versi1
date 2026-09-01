<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.uniforms.records.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Serah-terima Seragam Baru</h2>
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

                @if ($branches->isEmpty())
                    <p class="text-sm text-gray-500">
                        Tidak ada varian seragam dengan stok tersedia di outlet Anda. Hubungi GA untuk restock.
                    </p>
                @else
                    @php($ownBranch = $branches->first())
                    <form method="POST" action="{{ route('outlet.uniforms.records.store') }}" enctype="multipart/form-data" class="space-y-5"
                          x-data="{
                              tree: {{ Illuminate\Support\Js::from($stockTree) }},
                              branchId: '{{ $ownBranch->id }}',
                              branchLocationId: '{{ old('branch_location_id') }}',
                              branchLocations: @js($branchLocations),
                              get availableBranchLocations() { return this.branchLocations[this.branchId] || []; },
                              items: {{ Illuminate\Support\Js::from(old('items', [['type' => '', 'color' => '', 'uniform_stock_id' => '', 'qty' => 1, 'item_notes' => '']])) }},
                              get types() { return this.branchId ? Object.keys(this.tree[this.branchId] || {}) : []; },
                              colorsFor(item) { return (this.branchId && item.type) ? Object.keys((this.tree[this.branchId] || {})[item.type] || {}) : []; },
                              sizesFor(item) { return (this.branchId && item.type && item.color) ? ((this.tree[this.branchId] || {})[item.type] || {})[item.color] || [] : []; },
                              addItem() {
                                  this.items.push({ type: '', color: '', uniform_stock_id: '', qty: 1, item_notes: '' });
                              },
                              removeItem(index) {
                                  this.items.splice(index, 1);
                              },
                          }">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penerima *</label>
                                <input type="text" name="employee_name" required value="{{ old('employee_name') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penyerah *</label>
                                <input type="text" name="issued_by_name" required value="{{ old('issued_by_name') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                                <p class="text-sm text-gray-800 py-2">{{ $ownBranch->name }}</p>
                            </div>

                            <div x-show="availableBranchLocations.length > 0" x-cloak>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
                                <select name="branch_location_id" x-model="branchLocationId"
                                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Pilih Cabang --</option>
                                    <template x-for="loc in availableBranchLocations" :key="loc.id">
                                        <option :value="loc.id" x-text="loc.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Serah *</label>
                                <input type="date" name="issue_date" required value="{{ old('issue_date', now()->toDateString()) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        {{-- Item dinamis --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Daftar Item *</label>

                            <div class="space-y-3">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="relative grid grid-cols-12 gap-2 items-start border border-gray-100 rounded-md p-3 pr-8">
                                        <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                                class="absolute top-2 right-2 px-1.5 py-1 text-sm text-red-600 hover:bg-red-50 rounded-md">✕</button>

                                        <div class="col-span-6 sm:col-span-4">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Seragam</label>
                                            <select x-model="item.type" @change="item.color = ''; item.uniform_stock_id = ''" required
                                                    class="w-full rounded-md border-gray-300 shadow-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">-- Pilih --</option>
                                                <template x-for="t in types" :key="t">
                                                    <option :value="t" x-text="t"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="col-span-6 sm:col-span-3">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Warna</label>
                                            <select x-model="item.color" @change="item.uniform_stock_id = ''" :disabled="! item.type" required
                                                    class="w-full rounded-md border-gray-300 shadow-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                                <option value="">-- Pilih --</option>
                                                <template x-for="c in colorsFor(item)" :key="c">
                                                    <option :value="c" x-text="c"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="col-span-8 sm:col-span-3">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Ukuran</label>
                                            <select :name="`items[${index}][uniform_stock_id]`" x-model="item.uniform_stock_id" :disabled="! item.color" required
                                                    class="w-full rounded-md border-gray-300 shadow-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                                <option value="">-- Pilih --</option>
                                                <template x-for="s in sizesFor(item)" :key="s.id">
                                                    <option :value="s.id" x-text="'Size ' + s.size + ' (' + s.available + ' tersedia)'"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="col-span-4 sm:col-span-2">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                                            <input type="number" min="1" :name="`items[${index}][qty]`" x-model="item.qty" required
                                                   class="w-full rounded-md border-gray-300 shadow-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>

                                        <div class="col-span-12">
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Keterangan</label>
                                            <input type="text" :name="`items[${index}][item_notes]`" x-model="item.item_notes"
                                                   class="w-full rounded-md border-gray-300 shadow-sm py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addItem()"
                                    class="mt-2 inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
                                Buat Item
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Serah Terima</label>
                            <input type="file" name="issue_photo" accept="image/*"
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="text-xs text-gray-400 mt-1">Opsional, sebagai bukti foto saat barang/seragam diambil karyawan.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-signature-pad name="issued_by_signature" label="Tanda Tangan Penyerah" :required="false" />
                            <x-signature-pad name="signature" label="Tanda Tangan Penerima" :required="false" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="issue_notes" rows="2"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('issue_notes') }}</textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('outlet.uniforms.records.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
