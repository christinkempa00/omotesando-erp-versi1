<x-app-layout sidebar="finance">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Chart of Accounts
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Nama Akun</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tipe</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Induk</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($accounts as $row)
                                <tr class="{{ $row['account']->parent_id ? '' : 'bg-gray-50 font-semibold' }}">
                                    <td class="px-4 py-3 font-mono text-gray-800">{{ $row['account']->code }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ $row['account']->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $typeLabels[$row['account']->type] ?? $row['account']->type }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $row['account']->parent?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada akun.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
