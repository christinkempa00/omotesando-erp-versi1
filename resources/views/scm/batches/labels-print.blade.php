<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <x-back-link :href="route('scm.batches.show', $productionBatch)" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Label Batch {{ $productionBatch->batch_number }}
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
            @php $labels = $productionBatch->items->flatMap->labels; @endphp

            @if ($labels->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-400">
                    Belum ada label yang dicetak untuk batch ini.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 print:grid-cols-2 print:gap-2">
                    @foreach ($labels as $label)
                        <div class="bg-white shadow-sm rounded-lg p-4 flex items-center gap-4 break-inside-avoid print:border print:border-gray-300 print:shadow-none">
                            <div class="w-20 h-20 shrink-0 overflow-hidden [&>svg]:w-full [&>svg]:h-full">{!! $label->qr_code !!}</div>
                            <div class="min-w-0 text-left">
                                <p class="font-bold text-sm text-gray-900 uppercase leading-tight truncate">{{ $productionBatch->batch_number }}</p>
                                <p class="font-bold text-xs text-gray-800 uppercase leading-tight truncate">{{ $label->productionBatchItem->item_name }}</p>
                                <p class="font-bold text-[11px] text-gray-600 uppercase leading-tight truncate">
                                    {{ $label->productionBatchItem->qty }} {{ $label->productionBatchItem->unit }}
                                </p>
                                <div class="border-t border-gray-200 my-1.5"></div>
                                <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">Kode Label</p>
                                <p class="font-bold text-xs text-gray-900 truncate">{{ $label->label_code }}</p>
                                @if ($label->expiry_date)
                                    <p class="text-[10px] font-semibold text-gray-600 mt-0.5">ED: {{ $label->expiry_date->format('d M Y') }}</p>
                                @endif
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
