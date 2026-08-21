@props([
    'action' => null,
    'search' => true,
    'searchName' => 'search',
    'searchValue' => '',
    'searchPlaceholder' => 'Cari...',
    'resetUrl' => null,
])

<form method="GET" action="{{ $action ?? url()->current() }}" x-data="{ open: false }" class="flex items-center gap-3">
    @if ($search)
        <div class="relative flex-1 max-w-xs">
            <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $searchPlaceholder }}"
                   class="w-full rounded-full border-gray-200 pl-4 pr-9 py-2 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500">
            <svg class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" />
            </svg>
        </div>
    @endif

    <div class="relative shrink-0">
        <button type="button" @click="open = !open"
                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 6h16M7 12h10M10 18h4" />
            </svg>
            Filter
        </button>

        <div x-show="open" @click.outside="open = false" x-cloak
             class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white border border-gray-200 rounded-xl shadow-lg z-30" style="display:none;">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Filter</h3>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="px-5 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                {{ $slot }}
            </div>

            <div class="flex items-center gap-2 px-5 py-4 border-t border-gray-100">
                <button type="submit" class="flex-1 px-4 py-2 bg-gold-500 text-white text-sm font-medium rounded-md hover:bg-gold-600">
                    Terapkan Filter
                </button>
                <a href="{{ $resetUrl ?? url()->current() }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    Reset
                </a>
            </div>
        </div>
    </div>
</form>
