<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Pengajuan GA (GAR)
            </h2>
            <div class="flex flex-wrap gap-2" x-data="{ quickOpen: false }">
                <button type="button" @click="quickOpen = true"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Buat Permintaan
                </button>
                <a href="{{ route('ga.requests.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Buat Pengajuan
                </a>

                @include('ga.requests._quick-request-modal')
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter --}}
            <x-filter-bar :action="route('ga.requests.index')" :search-value="$search" search-placeholder="Cari nomor/pemohon/deskripsi..." :reset-url="route('ga.requests.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            {{-- Tabel --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Nomor</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Kategori</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Outlet</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Pemohon</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Total</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($requests as $req)
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('ga.requests.show', $req) }}'">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $req->request_number }}</td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ \App\Models\GA\GaRequest::categoryLabels()[$req->category] ?? $req->category }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $req->outletLabel() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $req->requester_name ?: ($req->requestedBy?->name ?? '—') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-700">
                                        Rp {{ number_format((float) $req->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($req->status) }}">
                                            {{ $statusLabels[$req->status] ?? $req->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                        <form method="POST" action="{{ route('ga.requests.destroy', $req) }}"
                                              onsubmit="return confirm('Hapus pengajuan {{ $req->request_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus" class="text-red-600 hover:text-red-800">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6M14 11v6"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
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
