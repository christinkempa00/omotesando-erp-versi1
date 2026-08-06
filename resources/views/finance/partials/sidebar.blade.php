@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->filter()
        ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
        ->take(2)
        ->join('');

    $navItems = [
        ['route' => 'finance.reports.general-ledger', 'pattern' => ['finance.reports.general-ledger'], 'label' => 'Buku Besar', 'icon' => 'book'],
        ['route' => 'finance.reports.expense-inventory', 'pattern' => ['finance.reports.expense-inventory'], 'label' => 'Beban & Persediaan', 'icon' => 'chart'],
        ['route' => 'finance.reports.payables-aging', 'pattern' => ['finance.reports.payables-aging'], 'label' => 'Umur Hutang', 'icon' => 'clock'],
        ['route' => 'finance.reports.balance-sheet', 'pattern' => ['finance.reports.balance-sheet'], 'label' => 'Neraca', 'icon' => 'scale'],
        ['route' => 'finance.chart-of-accounts.index', 'pattern' => ['finance.chart-of-accounts.*'], 'label' => 'Chart of Accounts', 'icon' => 'building'],
        ['route' => 'finance.transaction-mappings.index', 'pattern' => ['finance.transaction-mappings.*'], 'label' => 'Mapping Jurnal', 'icon' => 'link'],
    ];

    $visibleNavItems = collect($navItems);

    $icons = [
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />',
        'chart' => '<path d="M5 19V11" /><path d="M12 19V5" /><path d="M19 19v-7" />',
        'clock' => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" />',
        'scale' => '<path d="M12 3v18M7 21h10" /><path d="m5 8 3-4 3 4M2 8h6" /><path d="m16 8 3-4 3 4M13 8h6" /><path d="M5 8a3 3 0 1 0 0 4M2 8a3 3 0 1 0 6 0" /><path d="M19 8a3 3 0 1 0 0 4M16 8a3 3 0 1 0 6 0" />',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="1" /><path d="M9 8h1M14 8h1M9 12h1M14 12h1M9 16h1M14 16h1" />',
        'link' => '<path d="M9 17H7A5 5 0 0 1 7 7h2" /><path d="M15 7h2a5 5 0 1 1 0 10h-2" /><path d="M8 12h8" />',
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-slate-800">
        <a href="{{ route('finance.reports.general-ledger') }}" class="flex items-center gap-2 min-w-0">
            <span class="w-9 h-9 rounded-md bg-white flex items-center justify-center shrink-0 overflow-hidden p-0.5">
                <img src="{{ asset('images/allez-logo.jpg') }}" alt="Allez Group" class="w-full h-full object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-white font-semibold text-sm truncate">Allez Group</span>
                <span class="block text-slate-400 text-[11px] truncate">Finance</span>
            </span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($visibleNavItems as $item)
            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs(...$item['pattern']) ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    {!! $icons[$item['icon']] !!}
                </svg>
                {{ $item['label'] }}
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
