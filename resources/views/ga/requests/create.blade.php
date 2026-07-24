<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pengajuan GAR Baru
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

                <form method="POST" action="{{ route('ga.requests.store') }}"
                      x-data="gaRequestForm()" @submit="beforeSubmit">
                    @csrf

                    {{-- Cabang --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cabang / Outlet</label>
                        <select name="branch_id" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih Cabang --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kategori / Tujuan --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tujuan / Kategori</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($categoryLabels as $value => $label)
                                <label class="flex items-center gap-2 text-sm border rounded-md px-3 py-2 cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="category" value="{{ $value }}" required
                                           @checked(old('category') === $value)
                                           class="text-indigo-600 focus:ring-indigo-500">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Keterangan --}}
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <textarea name="description" rows="3" required
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Jelaskan detail kebutuhan / alasan pengajuan">{{ old('description') }}</textarea>
                    </div>

                    {{-- Item dinamis --}}
                    <div class="mb-5">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Daftar Item</label>
                            <button type="button" @click="addItem()"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                + Tambah Item
                            </button>
                        </div>

                        <div class="overflow-x-auto border rounded-md">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500">Nama Item</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-24">Qty</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-40">Harga Satuan</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500">Vendor</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-32">Subtotal</th>
                                        <th class="px-3 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="border-t">
                                            <td class="px-3 py-2">
                                                <input type="text" :name="`items[${index}][item_name]`" x-model="item.item_name" required
                                                       class="w-full rounded-md border-gray-300 text-sm">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" min="1" :name="`items[${index}][qty]`" x-model.number="item.qty" required
                                                       class="w-full rounded-md border-gray-300 text-sm">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" min="0" step="0.01" :name="`items[${index}][price_per_unit]`" x-model.number="item.price_per_unit" required
                                                       class="w-full rounded-md border-gray-300 text-sm">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" :name="`items[${index}][vendor_name]`" x-model="item.vendor_name"
                                                       class="w-full rounded-md border-gray-300 text-sm">
                                            </td>
                                            <td class="px-3 py-2 text-gray-600" x-text="formatCurrency(item.qty * item.price_per_unit)"></td>
                                            <td class="px-3 py-2 text-right">
                                                <button type="button" @click="removeItem(index)"
                                                        class="text-red-500 hover:text-red-700" x-show="items.length > 1">
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-gray-50 border-t">
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 text-right font-medium text-gray-700">Total</td>
                                        <td class="px-3 py-2 font-semibold text-gray-900" x-text="formatCurrency(grandTotal())"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('ga.requests.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Ajukan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function gaRequestForm() {
            return {
                items: [
                    { item_name: '', qty: 1, price_per_unit: 0, vendor_name: '' }
                ],
                addItem() {
                    this.items.push({ item_name: '', qty: 1, price_per_unit: 0, vendor_name: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                grandTotal() {
                    return this.items.reduce((sum, item) => sum + (item.qty * item.price_per_unit || 0), 0);
                },
                formatCurrency(value) {
                    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
                },
                beforeSubmit() {
                    // Alpine sudah bind name="items[n][...]" langsung ke input,
                    // jadi tidak perlu manipulasi tambahan sebelum submit.
                }
            }
        }
    </script>
</x-app-layout>
