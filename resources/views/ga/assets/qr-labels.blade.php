<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <x-back-link :href="route('ga.assets.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cetak Label QR ({{ $assets->count() }} aset)
                </h2>
            </div>
            <div class="print:hidden">
                <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Cetak
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 print:max-w-none print:px-0">
            @if ($assets->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-400">
                    Tidak ada aset yang cocok untuk dicetak labelnya.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 print:grid-cols-2 print:gap-2">
                    @foreach ($assets as $asset)
                        <div class="bg-white shadow-sm rounded-lg p-4 flex items-center gap-4 break-inside-avoid print:border print:border-gray-300 print:shadow-none">
                            <div class="w-20 h-20 shrink-0 overflow-hidden [&>svg]:w-full [&>svg]:h-full">{!! $qrSvgs[$asset->id] !!}</div>
                            <div class="min-w-0 text-left">
                                <p class="font-bold text-sm text-gray-900 uppercase leading-tight truncate">{{ $asset->branch?->name }}</p>
                                <p class="font-bold text-xs text-gray-800 uppercase leading-tight truncate">{{ \Illuminate\Support\Str::limit($asset->name, 28) }}</p>
                                @if ($asset->location)
                                    <p class="font-bold text-[11px] text-gray-600 uppercase leading-tight truncate">{{ $asset->location }}</p>
                                @endif
                                <div class="border-t border-gray-200 my-1.5"></div>
                                <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">Serial Number</p>
                                <p class="font-bold text-xs text-gray-900 truncate">{{ $asset->serial_number ?: $asset->asset_code }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        @media print {
            nav, header, .print\:hidden { display: none !important; }
        }
        .break-inside-avoid { break-inside: avoid; page-break-inside: avoid; }
    </style>
</x-app-layout>
