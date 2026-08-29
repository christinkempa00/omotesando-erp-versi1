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
                Dipakai untuk approval (tidak berubah). Memilih role akan menyarankan modul default di sebelah kanan.
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
                Ini yang menentukan menu/halaman yang muncul untuk user ini — bebas diubah, tidak harus ikut role.
                Untuk halaman yang punya tier, pilih "Lihat saja" (tanpa tombol tambah/edit/hapus) atau "Bisa edit".
            </p>
            <div class="space-y-3">
                @foreach ($modules as $module)
                    <div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="modules[]" value="{{ $module->id }}"
                                   :checked="modules.includes('{{ $module->id }}')"
                                   @change="toggleModule('{{ $module->id }}')"
                                   class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                            {{ $module->label }}
                        </label>

                        @foreach ($pagesByModuleKey[$module->key] ?? [] as $page)
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
                toggleRole(roleId) {
                    roleId = String(roleId);
                    const idx = this.roles.indexOf(roleId);
                    if (idx === -1) {
                        this.roles.push(roleId);
                        // Saran awal: auto-centang modul default role ini. Additive saja
                        // (tidak menghapus modul lain yang sudah dicentang manual), dan
                        // tidak ada aksi kebalikan saat role di-uncheck — checklist modul
                        // final tetap sepenuhnya di tangan IT sebelum submit.
                        const roleDefaults = (this.defaults[roleId] || []).map(String);
                        const isOutlet = this.outletRoleId !== null && roleId === this.outletRoleId;
                        roleDefaults.forEach((moduleId) => {
                            if (!this.modules.includes(moduleId)) {
                                this.modules.push(moduleId);
                                // Role Outlet default ke "Lihat saja" utk halaman yang
                                // ikut tersaran modulnya — role lain default "Bisa edit"
                                // (behavior lama). IT tetap bebas ubah manual sesudahnya.
                                (this.pageKeysByModuleId[moduleId] || []).forEach((pageKey) => {
                                    this.pageAccess[pageKey] = isOutlet ? 'view' : 'edit';
                                });
                            }
                        });
                    } else {
                        this.roles.splice(idx, 1);
                    }
                },
                toggleModule(moduleId) {
                    moduleId = String(moduleId);
                    const idx = this.modules.indexOf(moduleId);
                    if (idx === -1) {
                        this.modules.push(moduleId);
                        (this.pageKeysByModuleId[moduleId] || []).forEach((pageKey) => {
                            if (!(pageKey in this.pageAccess)) {
                                this.pageAccess[pageKey] = 'edit';
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
