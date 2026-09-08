<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-semibold text-xl text-ink leading-tight">
                Pengajuan Outlet {{ auth()->user()->branch?->name }}
            </h2>
            @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_REQUESTS))
                <div class="flex flex-wrap gap-2" x-data="{ quickOpen: false }">
                    <button type="button" @click="quickOpen = true"
                            class="inline-flex items-center btn-secondary">
                        Buat Permintaan
                    </button>
                    <a href="{{ route('outlet.requests.create') }}"
                       class="inline-flex items-center btn-primary">
                        Buat Pengajuan
                    </a>

                    @include('outlet.requests._quick-request-modal')
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <x-filter-bar :action="route('outlet.requests.index')" :search-value="$search" search-placeholder="Cari nomor/pemohon/deskripsi..." :reset-url="route('outlet.requests.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
            </x-filter-bar>

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Nomor</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kategori</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Pemohon</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse ($requests as $req)
                                <tr class="hover:bg-[#FDFBF4] cursor-pointer" onclick="window.location='{{ route('outlet.requests.show', $req) }}'">
                                    <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $req->request_number }}</td>
                                    <td class="px-4 py-3 text-ink">
                                        {{ \App\Models\GA\GaRequest::categoryLabels()[$req->category] ?? $req->category }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $req->requester_name ?: ($req->requestedBy?->name ?? '—') }}</td>
                                    <td class="px-4 py-3 text-right text-ink font-mono">
                                        Rp {{ number_format((float) $req->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($req->status) }}">
                                            {{ $statusLabels[$req->status] ?? $req->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-ink-muted">
                                        Belum ada pengajuan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div><x-table-footer :paginator="$requests" /></div>
        </div>
    </div>
</x-app-layout>
