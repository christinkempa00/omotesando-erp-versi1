<x-app-layout sidebar="finance">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mapping Akun Jurnal Otomatis
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <p class="text-sm text-gray-500">
                Akun debit/kredit yang dipakai jurnal otomatis (lihat
                <code class="text-xs">App\Services\Finance\JournalPoster</code>) tiap kali event
                terkait terjadi. Ubah di sini — tidak perlu ubah kode.
            </p>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Jenis Transaksi</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Akun Debit</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Akun Kredit</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($mappings as $mapping)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $typeLabels[$mapping->transaction_type] ?? $mapping->transaction_type }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $mapping->debitAccount->code }} — {{ $mapping->debitAccount->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $mapping->creditAccount->code }} — {{ $mapping->creditAccount->name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('finance.transaction-mappings.edit', $mapping) }}" class="text-indigo-600 hover:underline text-sm">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        Belum ada mapping — jalankan <code class="text-xs">TransactionAccountMappingSeeder</code>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
