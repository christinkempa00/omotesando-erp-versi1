<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Rekap Periodik
            </h2>
            <a href="{{ route('scm.reports.pdf', request()->query()) }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                Export PDF
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <x-filter-bar :action="route('scm.reports.index')" :search="false" :reset-url="route('scm.reports.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                    <select name="branch_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Outlet</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranchId == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Tanggal</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </x-filter-bar>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500">Total Pengiriman</p>
                    <p class="text-xl font-bold text-gray-800">{{ $summary['total_deliveries'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500">Total Qty Dikirim</p>
                    <p class="text-xl font-bold text-gray-800">{{ $summary['total_qty_sent'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500">Total Qty Diterima</p>
                    <p class="text-xl font-bold text-gray-800">{{ $summary['total_qty_received'] }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500">Ada Selisih</p>
                    <p class="text-xl font-bold text-status-discrepancy-fg">{{ $summary['total_discrepancy'] }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Dari</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Ke</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($deliveryNotes as $deliveryNote)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('scm.deliveries.show', $deliveryNote) }}'">
                                    <td class="px-4 py-3 font-mono text-gray-800">{{ $deliveryNote->delivery_code }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $deliveryNote->fromBranch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $deliveryNote->toBranch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\SCM\DeliveryNote::statusBadgeColor($deliveryNote->status) }}">
                                            {{ $statusLabels[$deliveryNote->status] ?? $deliveryNote->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $deliveryNote->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Tidak ada data untuk periode/outlet ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $deliveryNotes->links() }}</div>
        </div>
    </div>
</x-app-layout>
