@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->filter()
        ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
        ->take(2)
        ->join('');

    $navItems = [
        ['route' => 'outlet.dashboard', 'pattern' => ['outlet.dashboard'], 'label' => 'Dashboard', 'icon' => 'home', 'system_module' => null, 'module' => null],
        ['route' => 'outlet.requests.index', 'pattern' => ['outlet.requests.*'], 'label' => 'Pengajuan', 'icon' => 'list', 'system_module' => \App\Models\SystemModule::GA_REQUESTS, 'module' => \App\Models\Module::REQUESTS],
        ['route' => 'outlet.assets.index', 'pattern' => ['outlet.assets.*'], 'label' => 'Aset', 'icon' => 'archive', 'system_module' => \App\Models\SystemModule::GA_ASSETS, 'module' => \App\Models\Module::ASSETS],
        ['route' => 'outlet.uniforms.stocks.index', 'pattern' => ['outlet.uniforms.stocks.*'], 'label' => 'Seragam: Stok', 'icon' => 'shirt', 'system_module' => \App\Models\SystemModule::GA_UNIFORMS, 'module' => \App\Models\Module::UNIFORMS],
        ['route' => 'outlet.uniforms.records.index', 'pattern' => ['outlet.uniforms.records.*'], 'label' => 'Seragam: Serah Terima', 'icon' => 'shirt', 'system_module' => \App\Models\SystemModule::GA_UNIFORMS, 'module' => \App\Models\Module::UNIFORMS],
        ['route' => 'outlet.maintenance.index', 'pattern' => ['outlet.maintenance.*'], 'label' => 'Jadwal Pemeliharaan', 'icon' => 'wrench', 'system_module' => \App\Models\SystemModule::GA_MAINTENANCE, 'module' => \App\Models\Module::MAINTENANCE],
        ['route' => 'outlet.worklogs.index', 'pattern' => ['outlet.worklogs.*'], 'label' => 'Work Log', 'icon' => 'clipboard', 'system_module' => \App\Models\SystemModule::GA_WORKLOG, 'module' => \App\Models\Module::WORK_LOG],
    ];

    // Status mode pemeliharaan per halaman — sama key dgn grup ga. (toggle
    // dibagi bersama, bukan key outlet.* terpisah, lihat routes/web.php).
    $maintenanceByKey = \App\Models\SystemModule::pluck('is_under_maintenance', 'key');

    // Sama seperti sidebar GA — filter per akses PER USER (module_user),
    // bukan cuma status aktif/nonaktif global. Tanpa ini, akun Outlet yg
    // cuma dicentang IT utk 1 modul tetap lihat semua menu di sidebar.
    $moduleActiveByKey = \App\Models\Module::pluck('is_active', 'key');
    $userModuleKeys = Auth::user()->modules()->pluck('key')->all();
    $hasModuleAccess = fn (?string $key) => $key === null || in_array($key, $userModuleKeys, true);
    $navItems = collect($navItems)
        ->filter(fn ($item) => ($item['module'] === null || ($moduleActiveByKey[$item['module']] ?? true)) && $hasModuleAccess($item['module']))
        ->values()
        ->all();

    $icons = [
        'home' => '<path d="M4 10.5 12 4l8 6.5" /><path d="M6 9.5V19a1 1 0 0 0 1 1h4v-5a1 1 0 0 1 1-1h0a1 1 0 0 1 1 1v5h4a1 1 0 0 0 1-1V9.5" />',
        'clipboard' => '<rect x="6" y="4" width="12" height="16" rx="2" /><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" /><path d="M9 10h6M9 14h6M9 18h3" />',
        'archive' => '<rect x="4" y="7" width="16" height="13" rx="1.5" /><path d="M4 7l2-3h12l2 3" /><path d="M10 11h4" />',
        'shirt' => '<path d="M8 4 4 7l2 3 2-1.5V20h8V8.5L18 10l2-3-4-3-2 2h-4L8 4Z" />',
        'wrench' => '<path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13" /><path d="M3 6h.01M3 12h.01M3 18h.01" />',
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-white border-r border-gold-500/15 shadow-lg transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-gold-500/10">
        <a href="{{ route('outlet.dashboard') }}" class="flex items-center gap-2 min-w-0">
            <span class="w-9 h-9 rounded-lg bg-white border border-gold-500/15 flex items-center justify-center shrink-0 overflow-hidden p-0.5">
                <img src="{{ asset('images/allez-logo.jpg') }}" alt="Allez Group" class="w-full h-full object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-ink font-semibold text-sm truncate">Allez Group</span>
                <span class="block text-slate-400 text-[11px] truncate">{{ Auth::user()->identityLabel() }}</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-ink shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
               class="sidebar-nav-item {{ request()->routeIs(...$item['pattern']) ? 'sidebar-nav-item-active' : '' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons[$item['icon']] !!}
                </svg>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if ($item['system_module'] && ($maintenanceByKey[$item['system_module']] ?? false))
                    <span title="Sedang dalam mode pemeliharaan"
                          class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                        </svg>
                    </span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-gold-500/12 p-3 space-y-1">
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-2 py-2 rounded-lg transition {{ request()->routeIs('profile.edit') ? 'bg-gold-50' : 'hover:bg-gold-50' }}">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center text-white text-xs font-semibold shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-sm font-medium text-ink truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-2 py-2 rounded-lg text-sm text-slate-500 hover:bg-gold-50 hover:text-ink transition">
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
