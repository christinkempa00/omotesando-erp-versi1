<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Semua Pengajuan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <p class="text-sm text-ink-muted -mt-2">
                Pantau semua pengajuan GA, draft maupun yang sudah diajukan.
            </p>

            {{-- Filter --}}
            <x-filter-bar :action="route('head.requests.index')" :search-value="$search" search-placeholder="Cari nomor/pemohon/deskripsi..." :reset-url="route('head.requests.index')">
                <x-filter-pills name="status" label="Status" :options="$statusLabels" :selected="$selectedStatus" all-label="Semua Status" />
                <div>
                    <label class="block text-xs font-medium text-ink-muted mb-1">Jenis Pengajuan</label>
                    <select name="category" class="w-full rounded-md border-hairline text-sm shadow-sm focus:border-action focus:ring-action">
                        <option value="">Semua Jenis</option>
                        @foreach ($categoryLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filter-bar>

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Nomor</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Pemohon</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Outlet</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse ($requests as $req)
                                <tr class="hover:bg-[#FDFBF4]">
                                    <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $req->request_number }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $req->requester_name ?: ($req->requestedBy?->name ?? '—') }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $req->branch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($req->status) }}">
                                            {{ $statusLabels[$req->status] ?? $req->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $req->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('head.requests.show', $req) }}" class="text-action hover:text-action-hover font-medium">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-muted">
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
