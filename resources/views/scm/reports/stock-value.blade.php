<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Laporan Nilai Persediaan
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('scm.reports.stock-value.export.xlsx', request()->query()) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Export .xlsx
                </a>
                <a href="{{ route('scm.reports.stock-value.export.pdf', request()->query()) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Export .pdf
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($hasIncompleteValuation)
                <div class="rounded-md bg-status-discrepancy-bg border border-status-discrepancy-fg/30 p-4 text-sm text-status-discrepancy-fg">
                    Sebagian batch belum punya Biaya/Unit — nilainya ditampilkan sebagai "Belum ada harga" dan tidak dihitung dalam total.
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-xs text-gray-500">Total Nilai Persediaan (konsolidasi)</p>
                <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($grandTotal, 0, ',', '.') }}</p>
            </div>

            @if ($byBranch->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-sm text-gray-500">
                    Tidak ada stok tersisa saat ini.
                </div>
            @else
                @foreach ($byBranch as $branchName => $rows)
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-medium text-gray-800">{{ $branchName ?? '—' }}</h3>
                            <span class="text-sm font-semibold text-gray-600">
                                Rp {{ number_format($rows->sum('value'), 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Label</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Produk</th>
                                        <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                                        <th class="px-4 py-3 text-right font-medium text-gray-500">Biaya/Unit</th>
                                        <th class="px-4 py-3 text-right font-medium text-gray-500">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($rows as $row)
                                        <tr>
                                            <td class="px-4 py-3 font-mono text-gray-800">{{ $row['label_code'] }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['item_name'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-600">{{ $row['qty_on_hand'] }} {{ $row['unit'] }}</td>
                                            <td class="px-4 py-3 text-right text-gray-600">
                                                {{ $row['unit_cost'] !== null ? 'Rp '.number_format($row['unit_cost'], 0, ',', '.') : 'Belum ada harga' }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                                {{ $row['value'] !== null ? 'Rp '.number_format($row['value'], 0, ',', '.') : '—' }}
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
