<x-app-layout sidebar="purchasing">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Data Supplier
            </h2>
            @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                <a href="{{ route('purchasing.suppliers.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    + Supplier Baru
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
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Nama</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kontak</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Telepon</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Termin (hari)</th>
                                @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                                    <th class="px-4 py-3"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($suppliers as $supplier)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-800 font-medium">{{ $supplier->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $supplier->contact_person ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $supplier->phone ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $supplier->payment_terms_days ?? '—' }}</td>
                                    @if (auth()->user()->hasRole(\App\Models\Role::ADMIN))
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('purchasing.suppliers.edit', $supplier) }}" class="text-indigo-600 hover:underline text-sm">Edit</a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $suppliers->links() }}</div>
        </div>
    </div>
</x-app-layout>
