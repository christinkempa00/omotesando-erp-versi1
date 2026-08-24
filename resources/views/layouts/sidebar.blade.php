@php
    $navMain = [
        ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
    ];

    // Restrukturisasi 03/08/2026 — label Inggris + berjenjang: "Asset
    // Inventory" tetap halaman sendiri SEKALIGUS induk expand/collapse utk
    // "Asset Maintenance Schedule". "Operational Support" murni header
    // seksi (bukan link, tidak py halaman sendiri) yg menaungi "Asset
    // Request" & "Work Log" — lihat $navOperationalSupport di bawah.
    $navGa = [
        [
            // "Uniform Inventory" = daftar pemakaian (serah-terima) — tetap di
            // sini sebagai halaman utama. "Manajemen Stok" (nambah/hapus stok)
            // dipindah jadi sub-halaman "Stock Management" (11/08/2026), tapi
            // keduanya tetap 1 modul (module/system_module sama) & terkoneksi
            // (data stok & pemakaian sama-sama dari UniformStock/UniformMovement).
            'route' => 'ga.uniforms.records.index', 'pattern' => 'ga.uniforms.records.*', 'label' => 'Uniform Inventory', 'icon' => 'shirt', 'module' => \App\Models\Module::UNIFORMS, 'system_module' => \App\Models\SystemModule::GA_UNIFORMS,
            'children' => [
                ['route' => 'ga.uniforms.stocks.index', 'pattern' => ['ga.uniforms.stocks.*', 'ga.uniforms.movements.*'], 'label' => 'Stock Management', 'module' => \App\Models\Module::UNIFORMS, 'system_module' => \App\Models\SystemModule::GA_UNIFORMS],
            ],
        ],
        [
            'route' => 'ga.assets.index', 'pattern' => 'ga.assets.*', 'label' => 'Asset Inventory', 'icon' => 'archive', 'module' => \App\Models\Module::ASSETS, 'system_module' => \App\Models\SystemModule::GA_ASSETS,
            'children' => [
                ['route' => 'ga.maintenance.index', 'pattern' => 'ga.maintenance.*', 'label' => 'Asset Maintenance Schedule', 'module' => \App\Models\Module::MAINTENANCE, 'system_module' => \App\Models\SystemModule::GA_MAINTENANCE],
            ],
        ],
    ];

    $navOperationalSupport = [
        ['route' => 'ga.requests.index', 'pattern' => 'ga.requests.*', 'label' => 'Asset Request', 'icon' => 'clipboard', 'module' => \App\Models\Module::REQUESTS, 'system_module' => \App\Models\SystemModule::GA_REQUESTS],
        ['route' => 'ga.worklogs.index', 'pattern' => 'ga.worklogs.*', 'label' => 'Work Log', 'icon' => 'worklog', 'module' => \App\Models\Module::WORK_LOG, 'system_module' => \App\Models\SystemModule::GA_WORKLOG],
        // Fase B-1 (24/08/2026) — CRUD area pemeriksaan per outlet, prasyarat
        // form laporan foto (Fase B-2, belum dibangun).
        ['route' => 'ga.outlet-areas.index', 'pattern' => 'ga.outlet-areas.*', 'label' => 'Area Outlet', 'icon' => 'map-pin', 'module' => \App\Models\Module::OUTLET_MONITORING, 'system_module' => \App\Models\SystemModule::GA_OUTLET_MONITORING],
    ];

    // Status aktif/nonaktif per modul (dikontrol Head) — dibaca sekali di
    // sini. Sesuai Allez ERP Redesign: modul yang dinonaktifkan Head untuk
    // role ini SEMBUNYI total dari sidebar (bukan tampil redup/disabled),
    // supaya menu tidak berantakan.
    $moduleActiveByKey = \App\Models\Module::pluck('is_active', 'key');

    // Status mode pemeliharaan per halaman (dikontrol IT) — beda dari
    // moduleActiveByKey di atas: ini cuma badge peringatan, menu tetap bisa
    // diklik supaya user tahu dari awal sebelum masuk, bukan disembunyikan.
    $maintenanceByKey = \App\Models\SystemModule::pluck('is_under_maintenance', 'key');

    $icons = [
        'home' => '<path d="M4 10.5 12 4l8 6.5" /><path d="M6 9.5V19a1 1 0 0 0 1 1h4v-5a1 1 0 0 1 1-1h0a1 1 0 0 1 1 1v5h4a1 1 0 0 0 1-1V9.5" />',
        'chart' => '<path d="M5 19V11" /><path d="M12 19V5" /><path d="M19 19v-7" />',
        'clipboard' => '<rect x="6" y="4" width="12" height="16" rx="2" /><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" /><path d="M9 10h6M9 14h6M9 18h3" />',
        'archive' => '<rect x="4" y="7" width="16" height="13" rx="1.5" /><path d="M4 7l2-3h12l2 3" /><path d="M10 11h4" />',
        'wrench' => '<path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />',
        'shirt' => '<path d="M8 4 4 7l2 3 2-1.5V20h8V8.5L18 10l2-3-4-3-2 2h-4L8 4Z" />',
        'worklog' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" /><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M9 13h6M9 17h6" />',
        'map-pin' => '<path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11Z" /><circle cx="12" cy="10" r="2.5" />',
        'chevron' => '<path d="m9 6 6 6-6 6" />',
    ];

    $initials = collect(explode(' ', Auth::user()->name))
        ->filter()
        ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
        ->take(2)
        ->join('');
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-white border-r border-hairline transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-hairline">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
            <span class="w-9 h-9 rounded-md bg-accent-tint flex items-center justify-center shrink-0 overflow-hidden p-0.5">
                <img src="{{ asset('images/allez-logo.jpg') }}" alt="Allez Group" class="w-full h-full object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-ink font-semibold text-sm truncate">Allez Group</span>
                <span class="block text-ink-muted text-[11px] truncate">General Affairs</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-ink-muted hover:text-ink shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($navMain as $item)
            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs($item['pattern']) ? 'bg-accent-tint text-accent' : 'text-ink-muted hover:bg-gray-50 hover:text-ink' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons[$item['icon']] !!}
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        @foreach ($navGa as $item)
            @continue(! ($moduleActiveByKey[$item['module']] ?? true))
            @php
                $underMaintenance = $maintenanceByKey[$item['system_module']] ?? false;
                $children = $item['children'] ?? [];
                $visibleChildren = collect($children)->filter(fn ($c) => $moduleActiveByKey[$c['module']] ?? true)->values();
                $childActive = $visibleChildren->contains(fn ($c) => request()->routeIs(...(array) $c['pattern']));
            @endphp
            <div @if ($visibleChildren->isNotEmpty()) x-data="{ open: {{ (request()->routeIs($item['pattern']) || $childActive) ? 'true' : 'false' }} }" @endif>
                <div class="flex items-stretch gap-1">
                    <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
                       class="flex-1 flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                              {{ request()->routeIs($item['pattern']) ? 'bg-accent-tint text-accent' : 'text-ink-muted hover:bg-gray-50 hover:text-ink' }}">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            {!! $icons[$item['icon']] !!}
                        </svg>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if ($underMaintenance)
                            <span title="Sedang dalam mode pemeliharaan"
                                  class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                                </svg>
                            </span>
                        @endif
                    </a>
                    @if ($visibleChildren->isNotEmpty())
                        <button type="button" @click="open = !open"
                                class="px-2 rounded-lg text-ink-muted hover:bg-gray-50 hover:text-ink transition">
                            <svg class="w-4 h-4 shrink-0 transition-transform" :class="open ? 'rotate-90' : ''"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                {!! $icons['chevron'] !!}
                            </svg>
                        </button>
                    @endif
                </div>

                @if ($visibleChildren->isNotEmpty())
                    <div x-show="open" x-cloak class="ml-8 mt-1 space-y-1 border-l border-hairline pl-3">
                        @foreach ($visibleChildren as $child)
                            @php $childUnderMaintenance = $maintenanceByKey[$child['system_module']] ?? false; @endphp
                            <a href="{{ route($child['route']) }}" @click="sidebarOpen = false"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition
                                      {{ request()->routeIs(...(array) $child['pattern']) ? 'bg-accent-tint text-accent' : 'text-ink-muted hover:bg-gray-50 hover:text-ink' }}">
                                <span class="flex-1">{{ $child['label'] }}</span>
                                @if ($childUnderMaintenance)
                                    <span title="Sedang dalam mode pemeliharaan"
                                          class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                                        </svg>
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        @php
            $visibleOperationalSupport = collect($navOperationalSupport)->filter(fn ($c) => $moduleActiveByKey[$c['module']] ?? true)->values();
        @endphp
        @if ($visibleOperationalSupport->isNotEmpty())
            <p class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-ink-muted/70">Operational Support</p>
            @foreach ($visibleOperationalSupport as $item)
                @php $underMaintenance = $maintenanceByKey[$item['system_module']] ?? false; @endphp
                <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                          {{ request()->routeIs($item['pattern']) ? 'bg-accent-tint text-accent' : 'text-ink-muted hover:bg-gray-50 hover:text-ink' }}">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        {!! $icons[$item['icon']] !!}
                    </svg>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if ($underMaintenance)
                        <span title="Sedang dalam mode pemeliharaan"
                              class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                            </svg>
                        </span>
                    @endif
                </a>
            @endforeach
        @endif
    </nav>

    <div class="shrink-0 border-t border-hairline p-3 space-y-1">
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-2 py-2 rounded-lg transition {{ request()->routeIs('profile.edit') ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
            <div class="w-8 h-8 rounded-full bg-accent flex items-center justify-center text-white text-xs font-semibold shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-sm font-medium text-ink truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-ink-muted truncate">{{ Auth::user()->email }}</p>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-2 py-2 rounded-lg text-sm text-ink-muted hover:bg-gray-50 hover:text-ink transition">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <path d="M10 17l5-5-5-5" />
                    <path d="M15 12H3" />
                </svg>
                Log Out
            </button>
        </form>
    </div>
</aside>
