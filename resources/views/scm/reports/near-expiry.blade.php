<x-app-layout sidebar="scm">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Stok Mendekati Kedaluwarsa
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <form method="GET" action="{{ route('scm.reports.near-expiry') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Ambang batas (hari)</label>
                    <input type="number" name="days" min="1" value="{{ $days }}"
                           class="w-32 rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <button type="submit" class="px-4 py-2 bg-accent text-white text-sm font-medium rounded-md hover:opacity-90">
                    Terapkan
                </button>
            </form>

            @if ($groups->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-sm text-gray-500">
                    Tidak ada stok yang mendekati kedaluwarsa dalam {{ $days }} hari ke depan.
                </div>
            @else
                @foreach ($groups as $branchId => $balances)
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-medium text-gray-800">{{ $balances->first()->branch?->name ?? '—' }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Label</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Produk</th>
                                        <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Kedaluwarsa</th>
                                        <th class="px-4 py-3 text-right font-medium text-gray-500">Sisa Hari</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($balances as $balance)
                                        @php
                                            $stockable = $balance->stockable;
                                            $isBatchLabel = $stockable instanceof \App\Models\SCM\BatchLabel;
                                            $item = $isBatchLabel ? $stockable->productionBatchItem : $stockable->purchaseOrderItem;
                                            $code = $isBatchLabel ? $stockable->label_code : $stockable->purchaseOrderItem->purchaseOrder->po_number;
                                        @endphp
                                        <tr class="{{ $stockable->isNearExpiry(7) ? 'bg-status-discrepancy-bg/40' : '' }}">
                                            <td class="px-4 py-3 font-mono text-gray-800">{{ $code }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $item->item_name }}</td>
                                            <td class="px-4 py-3 text-right text-gray-600">{{ $balance->qty_on_hand }} {{ $item->unit }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $stockable->expiry_date->format('d M Y') }}</td>
                                            <td class="px-4 py-3 text-right font-semibold {{ $stockable->isNearExpiry(7) ? 'text-status-discrepancy-fg' : 'text-gray-600' }}">
                                                {{ $stockable->daysUntilExpiry() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
