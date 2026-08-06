@php
    $initials = collect(explode(' ', Auth::user()->name))
        ->filter()
        ->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))
        ->take(2)
        ->join('');

    $navItems = [
        ['route' => 'purchasing.purchase-requisitions.index', 'pattern' => ['purchasing.purchase-requisitions.*'], 'label' => 'Purchase Requisition', 'icon' => 'clipboard', 'roles' => [\App\Models\Role::OUTLET, \App\Models\Role::PURCHASING, \App\Models\Role::ADMIN]],
        ['route' => 'purchasing.purchase-orders.index', 'pattern' => ['purchasing.purchase-orders.*'], 'label' => 'Purchase Order', 'icon' => 'cart', 'roles' => [\App\Models\Role::PURCHASING, \App\Models\Role::GA, \App\Models\Role::FINANCE, \App\Models\Role::GUDANG, \App\Models\Role::ADMIN]],
        ['route' => 'purchasing.suppliers.index', 'pattern' => ['purchasing.suppliers.*'], 'label' => 'Data Supplier', 'icon' => 'building', 'roles' => [\App\Models\Role::PURCHASING, \App\Models\Role::GA, \App\Models\Role::FINANCE, \App\Models\Role::ADMIN]],
        ['route' => 'purchasing.supplier-invoices.index', 'pattern' => ['purchasing.supplier-invoices.*'], 'label' => 'Invoice Supplier', 'icon' => 'coins', 'roles' => [\App\Models\Role::FINANCE, \App\Models\Role::ADMIN]],
    ];

    $visibleNavItems = collect($navItems)->filter(fn ($item) => Auth::user()->hasRole(...$item['roles']))->values();

    $icons = [
        'clipboard' => '<rect x="6" y="4" width="12" height="16" rx="2" /><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1" /><path d="M9 10h6M9 14h6M9 18h3" />',
        'cart' => '<circle cx="9" cy="20" r="1.5" /><circle cx="18" cy="20" r="1.5" /><path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.8h7.7a2 2 0 0 0 2-1.6L21 8H6" />',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="1" /><path d="M9 8h1M14 8h1M9 12h1M14 12h1M9 16h1M14 16h1" />',
        'coins' => '<circle cx="9" cy="9" r="6" /><path d="M14.5 8a6 6 0 1 1 0 8" />',
    ];
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col bg-slate-900 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="h-16 shrink-0 flex items-center justify-between px-5 border-b border-slate-800">
        <a href="{{ $visibleNavItems->first()['route'] ? route($visibleNavItems->first()['route']) : route('profile.edit') }}" class="flex items-center gap-2 min-w-0">
            <span class="w-9 h-9 rounded-md bg-white flex items-center justify-center shrink-0 overflow-hidden p-0.5">
                <img src="{{ asset('images/allez-logo.jpg') }}" alt="Allez Group" class="w-full h-full object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-white font-semibold text-sm truncate">Allez Group</span>
                <span class="block text-slate-400 text-[11px] truncate">Purchasing</span>
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
