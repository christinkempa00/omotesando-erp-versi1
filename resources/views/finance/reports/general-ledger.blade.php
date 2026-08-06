<x-app-layout sidebar="finance">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Buku Besar (General Ledger)
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <form method="GET" action="{{ route('finance.reports.general-ledger') }}" class="bg-white shadow-sm rounded-lg p-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Akun *</label>
                    <select name="account_id" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                        <option value="">-- Pilih Akun --</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected($selectedAccountId == $acc->id)>{{ $acc->code }} — {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                </div>
                <div class="sm:col-span-4">
                    <button type="submit" class="px-4 py-2 bg-accent text-white text-sm font-medium rounded-md hover:opacity-90">Tampilkan</button>
                </div>
            </form>

            @if (! $account)
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-sm text-gray-500">
                    Pilih akun dulu utk melihat mutasinya.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-800">{{ $account->code }} — {{ $account->name }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">No. Jurnal</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-500">Keterangan</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Debit</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Kredit</th>
                                    <th class="px-4 py-3 text-right font-medium text-gray-500">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($lines as $line)
                                    <tr>
                                        <td class="px-4 py-3 font-mono text-gray-800">{{ $line['entry_number'] }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ \Illuminate\Support\Carbon::parse($line['entry_date'])->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $line['description'] }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600">{{ $line['debit'] > 0 ? number_format($line['debit'], 0, ',', '.') : '—' }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600">{{ $line['credit'] > 0 ? number_format($line['credit'], 0, ',', '.') : '—' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($line['balance'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">Tidak ada mutasi pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
