<x-app-layout sidebar="purchasing">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoice Supplier
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">No. Invoice</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">PO</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Supplier</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Jumlah</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Jatuh Tempo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($invoices as $invoice)
                                @php $po = $invoice->goodsReceipt->purchaseOrder; @endphp
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('purchasing.purchase-orders.show', $po) }}'">
                                    <td class="px-4 py-3 font-mono text-gray-800">{{ $invoice->invoice_number }}</td>
                                    <td class="px-4 py-3 text-gray-600 font-mono">{{ $po->po_number }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $po->supplier?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $invoice->due_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\Purchasing\SupplierInvoice::statusBadgeColor($invoice->status) }}">
                                            {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada invoice supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $invoices->links() }}</div>
        </div>
    </div>
</x-app-layout>
