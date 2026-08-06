<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Surat Jalan
            </h2>
            @if (auth()->user()->hasRole(\App\Models\Role::GUDANG, \App\Models\Role::ADMIN))
                <a href="{{ route('scm.deliveries.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    + Surat Jalan Baru
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
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada surat jalan.</td>
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
