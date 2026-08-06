<x-app-layout sidebar="purchasing">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('purchasing.purchase-requisitions.show', $purchaseRequisition)" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Buat PO dari {{ $purchaseRequisition->requisition_number }}
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

                <p class="text-sm text-gray-500 mb-5">
                    Diajukan oleh outlet: <span class="font-medium text-gray-800">{{ $purchaseRequisition->branch?->name }}</span> —
                    nama & qty barang mengikuti requisition ini, tinggal pilih supplier, tujuan pengiriman barang, & isi harga per item.
                </p>

                <form method="POST" action="{{ route('purchasing.purchase-orders.store-from-requisition', $purchaseRequisition) }}">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier *</label>
                            <select name="supplier_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tujuan Pengiriman Barang *</label>
                            <select name="branch_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Tujuan --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Biasanya Central Kitchen/Storage, bukan langsung ke outlet pemohon.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pesan *</label>
                            <input type="date" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Daftar Barang (dari Requisition)</label>
                        <div class="space-y-2">
                            @foreach ($purchaseRequisition->items as $index => $item)
                                <div class="grid grid-cols-12 gap-2 items-center border border-gray-100 rounded-md p-3">
                                    <input type="hidden" name="items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                                    <input type="hidden" name="items[{{ $index }}][qty]" value="{{ $item->qty }}">
                                    <input type="hidden" name="items[{{ $index }}][unit]" value="{{ $item->unit }}">
                                    <div class="col-span-12 sm:col-span-5 text-sm text-gray-800">{{ $item->item_name }}</div>
                                    <div class="col-span-6 sm:col-span-3 text-sm text-gray-600">{{ $item->qty }} {{ $item->unit }}</div>
                                    <div class="col-span-6 sm:col-span-4">
                                        <input type="number" min="0" step="0.01" name="items[{{ $index }}][unit_price]"
                                               value="{{ old('items.'.$index.'.unit_price') }}" placeholder="Harga/unit" required
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Buat PO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
