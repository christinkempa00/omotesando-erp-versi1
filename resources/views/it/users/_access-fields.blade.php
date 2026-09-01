@php
    $user = $user ?? null;
    $initialRoles = old('roles', $user?->roles->pluck('id')->all() ?? []);
    $initialModules = old('modules', $user?->modules->pluck('id')->all() ?? []);
    $savedPageAccess = $user ? $user->pagePermissions->pluck('access_level', 'page_key')->all() : [];
    $initialPageAccess = old('page_access', $savedPageAccess);
    $pageKeysByModuleId = collect($modules)
        ->mapWithKeys(fn ($module) => [(string) $module->id => collect($pagesByModuleKey[$module->key] ?? [])->pluck('key')->all()])
        ->all();

    // Peta role_id -> [module_id, ...] dlm bentuk array PHP polos (bukan
    // Collection ber-key campuran int/string dari DB::table) supaya aman
    // dipakai in_array() di bawah. Dipakai utk menaruh tiap modul langsung
    // di bawah role pemiliknya (bukan lagi 1 daftar modul terpisah) — modul
    // yang dipakai >1 role (mis. Aset dipakai GA & Outlet) sengaja muncul
    // dobel di bawah masing-masing, supaya jelas per role tanpa JS filter.
    $moduleIdsByRole = collect($roleModuleDefaults)
        ->mapWithKeys(fn ($ids, $roleId) => [(int) $roleId => collect($ids)->map(fn ($id) => (int) $id)->all()])
        ->all();

    // Modul yang tidak dimiliki role mana pun (harusnya tidak pernah terjadi
    // di data normal — semua modul GA/IT sudah py module_role) — jaring
    // pengaman supaya modul semacam ini tetap py tempat tampil, bukan malah
    // hilang total dari form & ke-drop diam-diam saat user ini disimpan.
    $ownedModuleIds = collect($moduleIdsByRole)->flatten()->unique()->all();
    $orphanModules = collect($modules)->reject(fn ($module) => in_array($module->id, $ownedModuleIds, true));

    // Aturan Dashboard otomatis: tercentang kalau salah satu Role yang
    // dicentang NAMANYA cocok (case-insensitive) dgn KODE Divisi yang
    // dipilih (mis. Role "GA" <-> Divisi "General Affair" berkode "GA" —
    // kasus akun ga@allez-group.com). Kalau tidak cocok/Divisi kosong
    // (mis. Amanda: Role GA tapi Divisi "Human Resources"), tidak
    // tercentang. Lihat syncDashboardModule() di script bawah.
    $dashboardModuleId = $modules->firstWhere('key', \App\Models\Module::DASHBOARD)?->id;
    $roleNamesById = collect($roles)->pluck('name', 'id');
    $selectedDivisionId = old('division_id', $user?->division_id);
    $initialDivisionCode = $selectedDivisionId
        ? $divisions->firstWhere('id', (int) $selectedDivisionId)?->code
        : null;
@endphp

<div
    x-data="userAccessForm({
        selectedRoles: @js($initialRoles),
        selectedModules: @js($initialModules),
        pageAccess: @js($initialPageAccess),
        pageKeysByModuleId: @js($pageKeysByModuleId),
        outletRoleId: @js($outletRoleId ? (string) $outletRoleId : null),
        dashboardModuleId: @js($dashboardModuleId ? (string) $dashboardModuleId : null),
        roleNamesById: @js($roleNamesById),
        initialDivisionCode: @js($initialDivisionCode),
    })"
    x-on:division-changed.window="onDivisionChanged($event.detail.code)"
>
    <h3 class="text-sm font-semibold text-gray-700 mb-1">Role & Akses Modul <span class="text-red-500">*</span></h3>
    <p class="text-xs text-gray-400 mb-3">
        Centang role, lalu centang modul yang muncul di bawahnya. Untuk halaman yang punya tier, pilih "Lihat saja"
        (tanpa tombol tambah/edit/hapus) atau "Bisa edit".
    </p>

    <div class="space-y-4">
        @foreach ($roles as $role)
            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                           :checked="roles.includes('{{ $role->id }}')"
                           @change="toggleRole('{{ $role->id }}')"
                           class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                    {{ $role->name }}
                </label>

                <div x-show="roles.includes('{{ $role->id }}')" x-cloak class="ml-6 mt-2 space-y-3">
                    @foreach ($modules as $module)
                        @continue(! in_array($module->id, $moduleIdsByRole[$role->id] ?? [], true))
                        @php $pages = $pagesByModuleKey[$module->key] ?? []; @endphp
                        <div>
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
        @endforeach

        @if ($orphanModules->isNotEmpty())
            <div>
                <p class="text-sm font-medium text-gray-700">Modul Lainnya</p>
                <div class="mt-2 space-y-2">
                    @foreach ($orphanModules as $module)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="modules[]" value="{{ $module->id }}"
                                   :checked="modules.includes('{{ $module->id }}')"
                                   @change="toggleModule('{{ $module->id }}')"
                                   class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                            {{ $module->label }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@once
    <script>
        function userAccessForm({
            selectedRoles, selectedModules, pageAccess, pageKeysByModuleId, outletRoleId,
            dashboardModuleId, roleNamesById, initialDivisionCode,
        }) {
            return {
                roles: selectedRoles.map(String),
                modules: selectedModules.map(String),
                pageAccess: { ...pageAccess },
                pageKeysByModuleId: pageKeysByModuleId,
                outletRoleId: outletRoleId,
                dashboardModuleId: dashboardModuleId,
                roleNamesById: roleNamesById,
                divisionCode: initialDivisionCode,
                init() {
                    this.syncDashboardModule();
                },
                toggleRole(roleId) {
                    roleId = String(roleId);
                    const idx = this.roles.indexOf(roleId);
                    if (idx === -1) {
                        // Cuma membuka daftar modul role ini (lihat markup) —
                        // TIDAK auto-centang (kecuali Dashboard, lihat
                        // syncDashboardModule). Checklist modul lainnya
                        // sepenuhnya di tangan IT, dicentang manual satu-satu.
                        this.roles.push(roleId);
                    } else {
                        this.roles.splice(idx, 1);
                    }
                    this.syncDashboardModule();
                },
                onDivisionChanged(code) {
                    this.divisionCode = code;
                    this.syncDashboardModule();
                },
                /**
                 * Dashboard tercentang otomatis kalau salah satu Role yang
                 * dicentang namanya cocok (case-insensitive) dgn kode Divisi
                 * yang sedang dipilih — mis. Role "GA" + Divisi berkode "GA"
                 * ("General Affair"). Tidak cocok/Divisi kosong -> otomatis
                 * tidak tercentang. Jalan tiap kali Role/Divisi berubah —
                 * centang/uncentang manual IT tetap nempel SELAMA Role/Divisi
                 * tidak diubah lagi setelahnya.
                 */
                syncDashboardModule() {
                    if (!this.dashboardModuleId) {
                        return;
                    }

                    const code = (this.divisionCode || '').toUpperCase();
                    const matches = code !== '' && this.roles.some(
                        (roleId) => (this.roleNamesById[roleId] || '').toUpperCase() === code
                    );

                    const idx = this.modules.indexOf(this.dashboardModuleId);
                    if (matches && idx === -1) {
                        this.modules.push(this.dashboardModuleId);
                    } else if (! matches && idx !== -1) {
                        this.modules.splice(idx, 1);
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
