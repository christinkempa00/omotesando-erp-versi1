@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->filter()
        ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
        ->take(2)
        ->join('');

    $navItems = [
        ['route' => 'head.dashboard', 'pattern' => ['head.dashboard'], 'label' => 'Dashboard', 'icon' => 'home', 'system_module' => \App\Models\SystemModule::HEAD_DASHBOARD],
        ['route' => 'head.requests.index', 'pattern' => ['head.requests.*'], 'label' => 'Semua Pengajuan', 'icon' => 'list', 'system_module' => \App\Models\SystemModule::HEAD_REQUESTS],
    ];

    // Modul GA (Aset/Seragam/Jadwal Pemeliharaan) digabung jadi satu menu
    // "GA" yang bisa dibuka-tutup — supaya menu utama tidak makin panjang
    // & membingungkan tiap kali ada modul GA baru ditambahkan nanti.
    $gaSubItems = [
        ['route' => 'head.assets.index', 'pattern' => ['head.assets.*'], 'label' => 'Inventaris Aset', 'icon' => 'archive', 'system_module' => \App\Models\SystemModule::HEAD_ASSETS],
        ['route' => 'head.uniforms.index', 'pattern' => ['head.uniforms.*'], 'label' => 'Inventaris Seragam', 'icon' => 'shirt', 'system_module' => \App\Models\SystemModule::HEAD_UNIFORMS],
        ['route' => 'head.maintenance.index', 'pattern' => ['head.maintenance.*'], 'label' => 'Jadwal Pemeliharaan', 'icon' => 'wrench', 'system_module' => \App\Models\SystemModule::HEAD_MAINTENANCE],
    ];
    $gaActive = collect($gaSubItems)->contains(fn ($item) => request()->routeIs(...$item['pattern']));

    $bottomItems = [
        ['route' => 'head.modules.index', 'pattern' => ['head.modules.*'], 'label' => 'Kontrol Modul', 'icon' => 'toggle', 'system_module' => \App\Models\SystemModule::HEAD_MODULES],
    ];

    // Status mode pemeliharaan per halaman (dikontrol IT) — badge peringatan
    // saja, menu tetap bisa diklik (lihat sidebar GA untuk alasan yang sama).
    $maintenanceByKey = \App\Models\SystemModule::pluck('is_under_maintenance', 'key');

    $icons = [
        'home' => '<path d="M4 10.5 12 4l8 6.5" /><path d="M6 9.5V19a1 1 0 0 0 1 1h4v-5a1 1 0 0 1 1-1h0a1 1 0 0 1 1 1v5h4a1 1 0 0 0 1-1V9.5" />',
        'clipboard' => '<rect x="6" y="4" width="12" height="16" rx="2" /><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" /><path d="M9 10h6M9 14h6M9 18h3" />',
        'archive' => '<rect x="4" y="7" width="16" height="13" rx="1.5" /><path d="M4 7l2-3h12l2 3" /><path d="M10 11h4" />',
        'shirt' => '<path d="M8 4 4 7l2 3 2-1.5V20h8V8.5L18 10l2-3-4-3-2 2h-4L8 4Z" />',
        'wrench' => '<path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />',
        'inbox' => '<path d="M4 12h4l2 3h4l2-3h4" /><path d="M4 12 5.5 5a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1L20 12v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6Z" />',
        'toggle' => '<rect x="2" y="7" width="20" height="10" rx="5" /><circle cx="16" cy="12" r="3" fill="currentColor" stroke="none" />',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13" /><path d="M3 6h.01M3 12h.01M3 18h.01" />',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />',
        'truck' => '<path d="M3 7h11v9H3z" /><path d="M14 11h4l3 3v2h-7z" /><circle cx="7" cy="18" r="1.5" /><circle cx="17.5" cy="18" r="1.5" />',
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-slate-800">
        <a href="{{ route('head.dashboard') }}" class="flex items-center gap-2 min-w-0">
            <span class="w-9 h-9 rounded-md bg-white flex items-center justify-center shrink-0 overflow-hidden p-0.5">
                <img src="{{ asset('images/allez-logo.jpg') }}" alt="Allez Group" class="w-full h-full object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-white font-semibold text-sm truncate">Allez Group</span>
                <span class="block text-slate-400 text-[11px] truncate">Head Office</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs(...$item['pattern']) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons[$item['icon']] !!}
                </svg>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if ($maintenanceByKey[$item['system_module']] ?? false)
                    <span title="Sedang dalam mode pemeliharaan"
                          class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-400/20 text-amber-400">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                        </svg>
                    </span>
                @endif
            </a>
        @endforeach

        {{-- Menu "GA" — satu pintu masuk utk semua modul monitoring GA
             (Aset/Seragam/Jadwal Pemeliharaan), dibuka-tutup spy tidak
             membuat menu utama makin panjang tiap ada modul baru. --}}
        <div x-data="{ open: {{ $gaActive ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                           {{ $gaActive ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons['folder'] !!}
                </svg>
                <span class="flex-1 text-left">GA</span>
                <svg class="w-4 h-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="mt-1 ml-4 pl-3 border-l border-slate-800 space-y-1" style="display:none;">
                @foreach ($gaSubItems as $item)
                    <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
                       class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm transition
                              {{ request()->routeIs(...$item['pattern']) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            {!! $icons[$item['icon']] !!}
                        </svg>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if ($maintenanceByKey[$item['system_module']] ?? false)
                            <span title="Sedang dalam mode pemeliharaan"
                                  class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-400/20 text-amber-400">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                                </svg>
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        @foreach ($bottomItems as $item)
            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs(...$item['pattern']) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons[$item['icon']] !!}
                </svg>
                <span class="flex-1">{{ $item['label'] }}</span>
                @if ($maintenanceByKey[$item['system_module']] ?? false)
                    <span title="Sedang dalam mode pemeliharaan"
                          class="inline-flex items-center gap-1 shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-400/20 text-amber-400">
                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                        </svg>
                    </span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-slate-800 p-3 space-y-1">
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-2 py-2 rounded-lg transition {{ request()->routeIs('profile.edit') ? 'bg-slate-800' : 'hover:bg-slate-800' }}">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-semibold shrink-0">
                {{ $initials }}
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-2 py-2 rounded-lg text-sm text-slate-400 hover:bg-slate-800 hover:text-white transition">
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
