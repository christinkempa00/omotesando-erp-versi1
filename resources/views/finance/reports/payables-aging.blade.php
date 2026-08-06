<x-app-layout sidebar="finance">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Umur Hutang (Aging Payable)
        </h2>
    </x-slot>

    @php
        $bucketLabels = [
            'not_due' => 'Belum Jatuh Tempo',
            'd1_30' => '1–30 Hari',
            'd31_60' => '31–60 Hari',
            'd60_plus' => '> 60 Hari',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($bucketLabels as $key => $label)
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="text-lg font-bold {{ $key === 'd60_plus' ? 'text-status-rejected-fg' : 'text-gray-800' }}">
                            Rp {{ number_format($totals[$key], 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>

            @foreach ($bucketLabels as $key => $label)
                @if ($buckets[$key]->isNotEmpty())
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-medium text-gray-800">{{ $label }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">No. Invoice</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Supplier</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Jatuh Tempo</th>
                                        <th class="px-4 py-3 text-right font-medium text-gray-500">Sisa Tagihan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($buckets[$key] as $row)
                                        <tr>
                                            <td class="px-4 py-3 font-mono text-gray-800">{{ $row['invoice']->invoice_number }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['supplier'] }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['invoice']->due_date->format('d M Y') }}</td>
                                            <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format($row['outstanding'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endforeach

            @if (collect($buckets)->every(fn ($b) => $b->isEmpty()))
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-sm text-gray-500">
                    Tidak ada hutang usaha yang belum lunas.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
