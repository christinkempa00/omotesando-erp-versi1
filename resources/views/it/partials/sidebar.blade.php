@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->filter()
        ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
        ->take(2)
        ->join('');

    $navItems = [
        ['route' => 'it.board.index', 'pattern' => ['it.board.*', 'it.labels.*'], 'label' => 'Papan Kerja', 'icon' => 'board'],
        ['route' => 'it.modules.index', 'pattern' => ['it.modules.*'], 'label' => 'Kontrol Modul', 'icon' => 'toggle'],
        ['route' => 'it.users.index', 'pattern' => ['it.users.*'], 'label' => 'Manajemen User', 'icon' => 'users'],
    ];

    $icons = [
        'toggle' => '<rect x="2" y="7" width="20" height="10" rx="5" /><circle cx="16" cy="12" r="3" fill="currentColor" stroke="none" />',
        'board' => '<rect x="3" y="4" width="18" height="16" rx="2" /><path d="M9 4v16M15 4v16" />',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-white border-r border-gold-500/15 shadow-lg transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-gold-500/10">
        <a href="{{ route('it.modules.index') }}" class="flex items-center gap-2 min-w-0">
            <span class="w-9 h-9 rounded-lg bg-white border border-gold-500/15 flex items-center justify-center shrink-0 overflow-hidden p-0.5">
                <img src="{{ asset('images/allez-logo.jpg') }}" alt="Allez Group" class="w-full h-full object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-ink font-semibold text-sm truncate">Allez Group</span>
                <span class="block text-slate-400 text-[11px] truncate">Kontrol IT</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-ink shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Kontrol IT</p>
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
               class="sidebar-nav-item {{ request()->routeIs(...$item['pattern']) ? 'sidebar-nav-item-active' : '' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons[$item['icon']] !!}
                </svg>
                {{ $item['label'] }}
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
