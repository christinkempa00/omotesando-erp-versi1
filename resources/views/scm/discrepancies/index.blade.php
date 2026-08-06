<x-app-layout sidebar="scm">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Laporan Selisih
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <p class="text-sm text-gray-500">
                Semua baris di sini dibuat otomatis oleh sistem saat qty diterima Outlet berbeda dari qty dikirim — bukan input manual.
            </p>

            <x-filter-bar :action="route('scm.discrepancies.index')" :search="false" :reset-url="route('scm.discrepancies.index')">
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

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Surat Jalan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Outlet</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Produk</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Ekspektasi</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Diterima</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Selisih</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($reports as $report)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-gray-800">{{ $report->deliveryNote?->delivery_code }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $report->deliveryNote?->toBranch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $report->deliveryNoteItem?->batchLabel?->productionBatchItem?->item_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $report->qty_expected }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $report->qty_received }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-status-discrepancy-bg text-status-discrepancy-fg">
                                            {{ $report->qty_diff > 0 ? '+' : '' }}{{ $report->qty_diff }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('scm.discrepancies.document', $report) }}" class="text-indigo-600 hover:underline text-xs">PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">Belum ada selisih tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $reports->links() }}</div>
        </div>
    </div>
</x-app-layout>
