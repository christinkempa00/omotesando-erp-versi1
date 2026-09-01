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

    <div x-data="quickCreateSelect({ createUrl: '{{ route('it.divisions.store') }}' })">
        <label class="block text-sm font-medium text-gray-700 mb-2">Divisi</label>
        <select name="division_id" x-ref="select" @change="onChange($event)"
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
            <option value="">-- Tidak ada --</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->id }}" @selected(old('division_id', $user->division_id ?? '') == $division->id)>{{ $division->name }}</option>
            @endforeach
            <option value="__new__">+ Tambah Divisi Baru...</option>
        </select>
        <div x-show="adding" x-cloak class="mt-2 flex gap-2">
            <input type="text" x-model="newName" @keydown.enter.prevent="create()" placeholder="Nama divisi baru"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
            <button type="button" @click="create()" :disabled="creating || !newName.trim()"
                    class="px-3 py-2 text-xs font-medium rounded-lg bg-gold-100 text-gold-700 hover:bg-gold-200 disabled:opacity-40"
                    x-text="creating ? 'Menambah...' : 'Tambah'"></button>
        </div>
        <p x-show="error" x-cloak x-text="error" class="mt-1 text-xs text-red-500"></p>
        <p class="mt-1 text-xs text-gray-400">Relevan untuk role yang butuh divisi, mis. Produksi.</p>
    </div>

    <div x-data="quickCreateSelect({ createUrl: '{{ route('it.branches.store') }}' })">
        <label class="block text-sm font-medium text-gray-700 mb-2">Branch / Outlet</label>
        <select name="branch_id" x-ref="select" @change="onChange($event)"
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
            <option value="">-- Tidak ada --</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(old('branch_id', $user->branch_id ?? '') == $branch->id)>{{ $branch->name }}</option>
            @endforeach
            <option value="__new__">+ Tambah Outlet Baru...</option>
        </select>
        <div x-show="adding" x-cloak class="mt-2 flex gap-2">
            <input type="text" x-model="newName" @keydown.enter.prevent="create()" placeholder="Nama outlet/branch baru"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
            <button type="button" @click="create()" :disabled="creating || !newName.trim()"
                    class="px-3 py-2 text-xs font-medium rounded-lg bg-gold-100 text-gold-700 hover:bg-gold-200 disabled:opacity-40"
                    x-text="creating ? 'Menambah...' : 'Tambah'"></button>
        </div>
        <p x-show="error" x-cloak x-text="error" class="mt-1 text-xs text-red-500"></p>
        <p class="mt-1 text-xs text-gray-400">Cuma relevan untuk role yang terikat 1 branch, mis. Gudang/Outlet.</p>
    </div>
</div>

@once
    <script>
        function quickCreateSelect({ createUrl }) {
            return {
                adding: false,
                newName: '',
                creating: false,
                error: '',
                onChange(event) {
                    this.adding = event.target.value === '__new__';
                    if (this.adding) {
                        this.error = '';
                    }
                },
                async create() {
                    const name = this.newName.trim();
                    if (!name) return;

                    this.creating = true;
                    this.error = '';

                    try {
                        const res = await fetch(createUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ name }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            throw new Error(data.message ?? 'Gagal menambah data.');
                        }

                        const select = this.$refs.select;
                        const option = document.createElement('option');
                        option.value = data.id;
                        option.textContent = data.name;
                        select.insertBefore(option, select.lastElementChild);
                        select.value = data.id;

                        this.adding = false;
                        this.newName = '';
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.creating = false;
                    }
                },
            };
        }
    </script>
@endonce
