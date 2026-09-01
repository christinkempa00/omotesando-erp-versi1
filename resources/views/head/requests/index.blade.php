<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Semua Pengajuan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <p class="text-sm text-gray-500 -mt-2">
                Pantau semua pengajuan GA, draft maupun yang sudah diajukan.
            </p>

            {{-- Filter --}}
            <x-filter-bar :action="route('head.requests.index')" :search-value="$search" search-placeholder="Cari nomor/pemohon/deskripsi..." :reset-url="route('head.requests.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Pengajuan</label>
                    <select name="category" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Jenis</option>
                        @foreach ($categoryLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Nomor</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Pemohon</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Outlet</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($requests as $req)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $req->request_number }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $req->requester_name ?: ($req->requestedBy?->name ?? '—') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $req->branch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($req->status) }}">
                                            {{ $statusLabels[$req->status] ?? $req->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('head.requests.show', $req) }}" class="text-indigo-600 hover:underline font-medium">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        Belum ada pengajuan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $requests->links() }}</div>
        </div>
    </div>
</x-app-layout>
