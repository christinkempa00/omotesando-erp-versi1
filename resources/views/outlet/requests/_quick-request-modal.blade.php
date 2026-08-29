{{--
    Modal "Buat Permintaan" versi Outlet — sama seperti punya GA, tapi
    outlet dikunci ke branch user yang login (tidak ada dropdown pilih
    outlet), sesuai prinsip branch = identitas, dipaksa server-side juga.
--}}
<div x-show="quickOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" @keydown.escape.window="quickOpen = false">
    <div class="fixed inset-0 bg-gray-900/60" @click="quickOpen = false"></div>

    <div
        x-data="{
            requested_date: '{{ now()->toDateString() }}',
            branch_location_id: '',
            availableBranchLocations: @js($branchLocations[auth()->user()->branch_id] ?? []),
            user_name: '',
            pic_name: '',
            urgency: 'low',
            needs_description: '',
            items: [{ item_name: '', qty: 1, unit: 'Pcs', notes: '', photo_link: '' }],
        }"
        @click.outside="quickOpen = false"
        class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
    >
        <div class="flex items-center justify-between px-5 py-4 bg-slate-900 rounded-t-lg">
            <h3 class="text-white text-sm font-semibold">Buat Permintaan</h3>
            <button type="button" @click="quickOpen = false" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('outlet.requests.quick.store') }}" class="p-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="requested_date" x-model="requested_date" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div x-show="availableBranchLocations.length > 0" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
                    <select name="branch_location_id" x-model="branch_location_id"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih Cabang --</option>
                        <template x-for="loc in availableBranchLocations" :key="loc.id">
                            <option :value="loc.id" x-text="loc.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User *</label>
                    <input type="text" name="user_name" x-model="user_name" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Person in charge *</label>
                    <input type="text" name="pic_name" x-model="pic_name" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Urgency</label>
                    <select name="urgency" x-model="urgency" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-gray-700">List Barang</p>
                    <button type="button" @click="items.push({ item_name: '', qty: 1, unit: 'Pcs', notes: '', photo_link: '' })"
                            class="px-3 py-1.5 bg-slate-800 text-white text-xs rounded-md hover:bg-slate-900">Buat Item</button>
                </div>

                <template x-for="(item, index) in items" :key="index">
                    <div class="border border-gray-100 rounded-md p-3 mb-2">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-500" x-text="'ITEM ' + (index + 1)"></span>
                            <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1" class="text-xs text-red-600 hover:underline">Hapus</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-2">
                            <input type="text" :name="`items[${index}][item_name]`" x-model="item.item_name" placeholder="Nama Item" required
                                   class="col-span-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="number" min="1" :name="`items[${index}][qty]`" x-model="item.qty" placeholder="Qty" required
                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="text" :name="`items[${index}][unit]`" x-model="item.unit" placeholder="Satuan"
                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <input type="text" :name="`items[${index}][notes]`" x-model="item.notes" placeholder="Notes (opsional)"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </template>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Penjelasan kebutuhan</label>
                <textarea name="needs_description" x-model="needs_description" rows="2"
                          class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="quickOpen = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Kirim ke Telegram
                </button>
            </div>
        </form>
    </div>
</div>
