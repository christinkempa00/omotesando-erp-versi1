{{--
    Modal "Minta Aset" — permintaan cepat yang dikirim sebagai notifikasi
    Telegram (bukan alur GAR berjenjang). Alpine `quickOpen` datang dari
    x-data induk di index.blade.php.
--}}
<div x-show="quickOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" @keydown.escape.window="quickOpen = false">
    <div class="fixed inset-0 bg-gray-900/60" @click="quickOpen = false"></div>

    <div
        x-data="{
            requested_date: '{{ now()->toDateString() }}',
            branch_id: '',
            branch_location_id: '',
            branchLocations: @js($branchLocations),
            get availableBranchLocations() { return this.branchLocations[this.branch_id] || []; },
            user_name: '',
            pic_name: '',
            urgency: 'low',
            needs_description: '',
            items: [{ item_name: '', qty: 1, unit: 'Pcs', notes: '', photo_link: '' }],
            branchName(id) {
                const map = { @foreach ($branches as $branch) {{ $branch->id }}: '{{ addslashes($branch->name) }}', @endforeach };
                return map[id] || '-';
            },
            outletLabel() {
                const loc = this.availableBranchLocations.find(l => l.id == this.branch_location_id);
                return loc ? this.branchName(this.branch_id) + ' — ' + loc.name : this.branchName(this.branch_id);
            },
            urgencyLabel(v) {
                return { low: 'Low', medium: 'Medium', high: 'High' }[v] || v;
            },
            get preview() {
                const dateObj = this.requested_date ? new Date(this.requested_date + 'T00:00:00') : null;
                const dateStr = dateObj ? dateObj.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-';
                let lines = [];
                lines.push('GA Request');
                lines.push('');
                lines.push('Tanggal: ' + dateStr);
                lines.push('Outlet: ' + this.outletLabel());
                lines.push('User: ' + (this.user_name || '-'));
                lines.push('Person in charge: ' + (this.pic_name || '-'));
                lines.push('Status Urgency: ' + this.urgencyLabel(this.urgency));
                lines.push('');
                lines.push('List barang:');
                this.items.forEach((it, i) => {
                    lines.push((i + 1) + '. ' + (it.item_name || 'Item Tanpa Nama') + ' - ' + (it.qty || 0) + ' ' + (it.unit || 'Pcs'));
                });
                lines.push('');
                lines.push('Penjelasan kebutuhan:');
                lines.push('1. ' + (this.needs_description || '-'));
                const links = this.items.map(it => it.photo_link).filter(l => l);
                if (links.length) {
                    lines.push('');
                    lines.push('Link/foto barang:');
                    links.forEach((l, i) => lines.push((i + 1) + '. ' + l));
                }
                return lines.join('\n');
            }
        }"
        @click.outside="quickOpen = false"
        class="relative bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto"
    >
        <div class="flex items-center justify-between px-5 py-4 bg-slate-900 rounded-t-lg">
            <h3 class="text-white text-sm font-semibold">📢 PEMBERITAHUAN PERMINTAAN</h3>
            <button type="button" @click="quickOpen = false" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('ga.requests.quick.store') }}" class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-5">
            @csrf

            <div class="lg:col-span-2 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="requested_date" x-model="requested_date" required
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Outlet *</label>
                        <select name="branch_id" x-model="branch_id" @change="branch_location_id = ''" required
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih Outlet --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
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
                        <input type="text" name="user_name" x-model="user_name" required placeholder="Contoh: Deny"
                               class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Person in charge *</label>
                        <input type="text" name="pic_name" x-model="pic_name" required placeholder="Contoh: Deny"
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
                        <div>
                            <p class="text-sm font-semibold text-gray-700">List Barang</p>
                            <p class="text-xs text-gray-400">Isi item, qty, satuan, notes, dan link/foto barang.</p>
                        </div>
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
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-2">
                            <input type="text" :name="`items[${index}][photo_link]`" x-model="item.photo_link" placeholder="Link/foto barang (opsional)"
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Penjelasan kebutuhan</label>
                    <textarea name="needs_description" x-model="needs_description" rows="2" placeholder="Contoh: Alat kebersihan untuk OB"
                              class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="bg-slate-900 rounded-lg p-4 sticky top-0">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-300">Preview Telegram</span>
                        <span class="text-[10px] px-2 py-0.5 bg-slate-700 text-slate-300 rounded-full">Announcement</span>
                    </div>
                    <pre class="text-xs text-slate-100 whitespace-pre-wrap font-sans" x-text="preview"></pre>
                </div>
            </div>

            <div class="lg:col-span-3 flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <button type="button" @click="quickOpen = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Kirim ke Telegram
                </button>
            </div>
        </form>
    </div>
</div>
