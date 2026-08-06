<x-app-layout sidebar="finance">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Neraca Sederhana
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="rounded-md bg-status-pending-bg border border-status-pending-fg/30 p-4 text-sm text-status-pending-fg">
                Neraca ini menampilkan saldo akhir tiap akun Aset/Liabilitas/Ekuitas apa adanya —
                belum ada penutupan (closing entry) otomatis dari akun Pendapatan/Beban ke Ekuitas.
            </div>

            @foreach ([
                ['type' => \App\Models\Finance\ChartOfAccount::TYPE_ASSET, 'label' => 'Aset', 'total' => $totalAsset],
                ['type' => \App\Models\Finance\ChartOfAccount::TYPE_LIABILITY, 'label' => 'Liabilitas', 'total' => $totalLiability],
                ['type' => \App\Models\Finance\ChartOfAccount::TYPE_EQUITY, 'label' => 'Ekuitas', 'total' => $totalEquity],
            ] as $section)
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-medium text-gray-800">{{ $section['label'] }}</h3>
                        <span class="text-sm font-semibold text-gray-600">Rp {{ number_format($section['total'], 0, ',', '.') }}</span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rows->where('account.type', $section['type']) as $row)
                                <tr>
                                    <td class="px-6 py-2.5 text-gray-600 font-mono w-24">{{ $row['account']->code }}</td>
                                    <td class="px-4 py-2.5 text-gray-800">{{ $row['account']->name }}</td>
                                    <td class="px-6 py-2.5 text-right text-gray-800">Rp {{ number_format($row['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            <div class="bg-white shadow-sm rounded-lg p-4 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">Total Liabilitas + Ekuitas</span>
                <span class="text-sm font-bold text-gray-800">Rp {{ number_format($totalLiability + $totalEquity, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
