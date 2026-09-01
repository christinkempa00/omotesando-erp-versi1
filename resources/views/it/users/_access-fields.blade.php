@php
    $user = $user ?? null;
    $initialRoles = old('roles', $user?->roles->pluck('id')->all() ?? []);
    $initialModules = old('modules', $user?->modules->pluck('id')->all() ?? []);
    $savedPageAccess = $user ? $user->pagePermissions->pluck('access_level', 'page_key')->all() : [];
    $initialPageAccess = old('page_access', $savedPageAccess);
    $pageKeysByModuleId = collect($modules)
        ->mapWithKeys(fn ($module) => [(string) $module->id => collect($pagesByModuleKey[$module->key] ?? [])->pluck('key')->all()])
        ->all();
@endphp

<div
    x-data="userAccessForm({
        roleModuleDefaults: @js($roleModuleDefaults),
        selectedRoles: @js($initialRoles),
        selectedModules: @js($initialModules),
        pageAccess: @js($initialPageAccess),
        pageKeysByModuleId: @js($pageKeysByModuleId),
        outletRoleId: @js($outletRoleId ? (string) $outletRoleId : null),
    })"
>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Role <span class="text-red-500">*</span></h3>
            <p class="text-xs text-gray-400 mb-3">
                Dipakai untuk approval (tidak berubah). Modul di sebelah kanan menyesuaikan otomatis dgn role yang dicentang di sini.
            </p>
            <div class="space-y-2">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               :checked="roles.includes('{{ $role->id }}')"
                               @change="toggleRole('{{ $role->id }}')"
                               class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Akses Modul <span class="text-red-500">*</span></h3>
            <p class="text-xs text-gray-400 mb-3">
                Bebas diubah, tidak harus ikut role — centang role di kiri dulu supaya modulnya muncul di sini, atau
                cari langsung kalau modul yang dicari belum tampil. Untuk halaman yang punya tier, pilih "Lihat saja"
                (tanpa tombol tambah/edit/hapus) atau "Bisa edit".
            </p>
            <label class="flex items-center gap-2 mb-3 text-xs text-gray-500">
                <input type="checkbox" x-model="showAllModules" class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                Tampilkan semua modul (jangan filter per role)
            </label>
            <div class="space-y-3">
                @foreach ($modules as $module)
                    @php $pages = $pagesByModuleKey[$module->key] ?? []; @endphp
                    <div x-show="showAllModules || moduleVisible('{{ $module->id }}')" x-cloak>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="modules[]" value="{{ $module->id }}"
                                   :checked="modules.includes('{{ $module->id }}')"
                                   @change="toggleModule('{{ $module->id }}')"
                                   class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                            {{ $module->label }}

                            @if (count($pages) === 1)
                                <span x-show="modules.includes('{{ $module->id }}')" x-cloak
                                      class="flex items-center gap-3 text-xs text-gray-500">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="page_access[{{ $pages[0]['key'] }}]" value="view"
                                               x-model="pageAccess['{{ $pages[0]['key'] }}']"
                                               class="border-gray-300 text-gold-600 focus:ring-gold-500">
                                        Lihat saja
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="page_access[{{ $pages[0]['key'] }}]" value="edit"
                                               x-model="pageAccess['{{ $pages[0]['key'] }}']"
                                               class="border-gray-300 text-gold-600 focus:ring-gold-500">
                                        Bisa edit
                                    </label>
                                </span>
                            @endif
                        </label>

                        @if (count($pages) > 1)
                            @foreach ($pages as $page)
                                <div x-show="modules.includes('{{ $module->id }}')" x-cloak
                                     class="ml-6 mt-1 flex items-center gap-4 text-xs text-gray-500">
                                    <span class="text-gray-400">{{ $page['label'] }}:</span>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="page_access[{{ $page['key'] }}]" value="view"
                                               x-model="pageAccess['{{ $page['key'] }}']"
                                               class="border-gray-300 text-gold-600 focus:ring-gold-500">
                                        Lihat saja
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="page_access[{{ $page['key'] }}]" value="edit"
                                               x-model="pageAccess['{{ $page['key'] }}']"
                                               class="border-gray-300 text-gold-600 focus:ring-gold-500">
                                        Bisa edit
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@once
    <script>
        function userAccessForm({ roleModuleDefaults, selectedRoles, selectedModules, pageAccess, pageKeysByModuleId, outletRoleId }) {
            return {
                roles: selectedRoles.map(String),
                modules: selectedModules.map(String),
                defaults: roleModuleDefaults,
                pageAccess: { ...pageAccess },
                pageKeysByModuleId: pageKeysByModuleId,
                outletRoleId: outletRoleId,
                showAllModules: false,
                /**
                 * Modul tampil kalau: (a) jadi default salah satu role yang
                 * SEDANG dicentang, ATAU (b) modul ini sudah tercentang utk
                 * user ini (supaya modul yang sengaja diberi manual di luar
                 * role tidak "hilang" dari tampilan pas buka form edit).
                 */
                moduleVisible(moduleId) {
                    moduleId = String(moduleId);
                    if (this.modules.includes(moduleId)) {
                        return true;
                    }
                    return this.roles.some((roleId) => (this.defaults[roleId] || []).map(String).includes(moduleId));
                },
                toggleRole(roleId) {
                    roleId = String(roleId);
                    const idx = this.roles.indexOf(roleId);
                    if (idx === -1) {
                        // Cuma menampilkan modul yang relevan (lihat moduleVisible) —
                        // TIDAK auto-centang. Checklist modul final sepenuhnya di
                        // tangan IT, dicentang manual satu-satu.
                        this.roles.push(roleId);
                    } else {
                        this.roles.splice(idx, 1);
                    }
                },
                toggleModule(moduleId) {
                    moduleId = String(moduleId);
                    const idx = this.modules.indexOf(moduleId);
                    if (idx === -1) {
                        this.modules.push(moduleId);
                        // Default tier saat modul BARU dicentang manual: Outlet
                        // ke "Lihat saja", role lain ke "Bisa edit". IT tetap
                        // bebas ubah manual sesudahnya.
                        const isOutlet = this.outletRoleId !== null && this.roles.includes(this.outletRoleId);
                        (this.pageKeysByModuleId[moduleId] || []).forEach((pageKey) => {
                            if (!(pageKey in this.pageAccess)) {
                                this.pageAccess[pageKey] = isOutlet ? 'view' : 'edit';
                            }
                        });
                    } else {
                        this.modules.splice(idx, 1);
                    }
                },
            };
        }
    </script>
@endonce
