<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div class="print:hidden">
                <x-back-link :href="route('ga.assets.show', $asset)" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Label QR — {{ $asset->asset_code }}
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
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8 print:max-w-none print:px-0">
            <div class="bg-white shadow-sm rounded-lg p-6 flex items-center gap-6 print:shadow-none print:rounded-none print:border print:border-gray-300">
                <div class="w-32 h-32 shrink-0 overflow-hidden [&>svg]:w-full [&>svg]:h-full">{!! $qrSvg !!}</div>
                <div class="min-w-0">
                    <p class="font-bold text-xl text-gray-900 uppercase leading-tight truncate">{{ $asset->branch?->name }}</p>
                    <p class="font-bold text-base text-gray-800 uppercase leading-tight truncate mt-1">{{ $asset->name }}</p>
                    @if ($asset->location)
                        <p class="font-bold text-sm text-gray-600 uppercase leading-tight truncate mt-1">{{ $asset->location }}</p>
                    @endif

                    <div class="border-t border-gray-200 my-3"></div>

                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Serial Number</p>
                    <p class="font-bold text-lg text-gray-900">{{ $asset->serial_number ?: $asset->asset_code }}</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            nav, header, .print\:hidden { display: none !important; }
        }
    </style>
</x-app-layout>
