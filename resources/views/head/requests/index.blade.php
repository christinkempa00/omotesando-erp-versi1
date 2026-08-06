<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Semua Pengajuan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <p class="text-sm text-gray-500 -mt-2">
                Pantau semua pengajuan sejak dibuat — termasuk yang belum giliran Anda — agar bisa bersiap
                sebelum tahap approval sampai ke Anda.
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
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Progres Approval</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($requests as $req)
                                @php $currentStep = $req->currentApprovalStep(); @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $req->request_number }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $req->requester_name ?: ($req->requestedBy?->name ?? '—') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $req->branch?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1">
                                            @foreach ($req->approvals->sortBy('step') as $approval)
                                                @php
                                                    $dotColor = match ($approval->status) {
                                                        'approved' => 'bg-green-500',
                                                        'rejected' => 'bg-red-500',
                                                        default => ($currentStep && $currentStep->is($approval)) ? 'bg-blue-500 animate-pulse' : 'bg-gray-300',
                                                    };
                                                @endphp
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $dotColor }}"
                                                      title="Step {{ $approval->step }} · {{ $approval->approver_role }} · {{ ucfirst($approval->status) }}"></span>
                                                @if (! $loop->last)
                                                    <span class="w-3 h-px bg-gray-300 shrink-0"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1">
                                            @if ($req->hasRejectedStep())
                                                Ditolak di step {{ $req->approvals->firstWhere('status', 'rejected')?->step }}
                                            @elseif ($currentStep)
                                                Menunggu {{ $currentStep->approver_role }}
                                            @else
                                                Selesai
                                            @endif
                                        </p>
                                    </td>
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
