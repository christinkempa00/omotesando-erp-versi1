<x-app-layout sidebar="purchasing">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Purchase Order
            </h2>
            @if (auth()->user()->hasRole(\App\Models\Role::GA, \App\Models\Role::ADMIN))
                <a href="{{ route('purchasing.purchase-orders.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    + PO Baru
                </a>
            @endif
        </div>
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
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Nomor PO</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Supplier</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tujuan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kategori</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('purchasing.purchase-orders.show', $order) }}'">
                                    <td class="px-4 py-3 font-mono text-gray-800">{{ $order->po_number }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $order->supplier?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $order->branch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $categoryLabels[$order->category] ?? $order->category }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\Purchasing\PurchaseOrder::statusBadgeColor($order->status) }}">
                                            {{ $statusLabels[$order->status] ?? $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $order->order_date->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada Purchase Order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
