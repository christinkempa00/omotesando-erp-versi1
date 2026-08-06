<x-app-layout sidebar="finance">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Laporan Beban Operasional & Persediaan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <form method="GET" action="{{ route('finance.reports.expense-inventory') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <button type="submit" class="px-4 py-2 bg-accent text-white text-sm font-medium rounded-md hover:opacity-90">Terapkan</button>
            </form>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500">Total Beban Operasional (Konsolidasi)</p>
                    <p class="text-xl font-bold text-gray-800">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-xs text-gray-500">Total Persediaan (Konsolidasi)</p>
                    <p class="text-xl font-bold text-gray-800">Rp {{ number_format($totalInventory, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Per Outlet</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Outlet</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Beban Operasional</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Persediaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($byBranch as $branchName => $amounts)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $branchName }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($amounts['expense'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($amounts['inventory'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-gray-500">Tidak ada data pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
