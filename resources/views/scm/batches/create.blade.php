<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('scm.batches.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Batch Produksi Baru
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($materialRequests->isEmpty())
                    <p class="text-sm text-gray-500">
                        Belum ada Pengajuan Bahan yang disetujui untuk dijadikan batch.
                        <a href="{{ route('scm.materials.index') }}" class="text-indigo-600 hover:underline">Lihat Pengajuan Bahan</a>.
                    </p>
                @else
                    <form method="POST" action="{{ route('scm.batches.store') }}"
                          x-data="{ items: {{ Illuminate\Support\Js::from(old('items', [['item_name' => '', 'qty' => 1, 'unit' => 'Pcs', 'unit_cost' => '']])) }} }">
                        @csrf

                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dari Pengajuan Bahan *</label>
                            <select name="material_request_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Pengajuan --</option>
                                @foreach ($materialRequests as $mr)
                                    <option value="{{ $mr->id }}" @selected(old('material_request_id') == $mr->id)>
                                        {{ $mr->request_number }} — {{ $mr->branch?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produk Hasil Batch *</label>
                            <p class="text-xs text-gray-400 mb-2">Satu batch bisa menghasilkan lebih dari satu produk berbeda. Biaya/unit opsional — dipakai Laporan Nilai Persediaan.</p>
                            <div class="space-y-2">
                                <template x-for="(item, index) in items" :key="index">
                                    <div class="grid grid-cols-12 gap-2 items-start border border-gray-100 rounded-md p-3">
                                        <div class="col-span-12 sm:col-span-5">
                                            <input type="text" :name="`items[${index}][item_name]`" x-model="item.item_name"
                                                   placeholder="Nama produk" required
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        </div>
                                        <div class="col-span-4 sm:col-span-2">
                                            <input type="number" min="1" :name="`items[${index}][qty]`" x-model="item.qty"
                                                   placeholder="Qty" required
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        </div>
                                        <div class="col-span-4 sm:col-span-2">
                                            <input type="text" :name="`items[${index}][unit]`" x-model="item.unit"
                                                   placeholder="Satuan" required
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        </div>
                                        <div class="col-span-4 sm:col-span-2">
                                            <input type="number" min="0" step="0.01" :name="`items[${index}][unit_cost]`" x-model="item.unit_cost"
                                                   placeholder="Biaya/unit"
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        </div>
                                        <div class="col-span-12 sm:col-span-1 flex items-center justify-end">
                                            <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                                                    class="px-2 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-md">✕</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="items.push({ item_name: '', qty: 1, unit: 'Pcs', unit_cost: '' })"
                                    class="mt-2 inline-flex items-center px-3 py-1.5 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
                                + Tambah produk
                            </button>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Buat Batch
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
