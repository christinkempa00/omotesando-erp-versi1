<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Approval Inbox
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-sm text-gray-500">Total Menunggu Approval</p>
                <p class="text-2xl font-bold text-status-pending-fg">{{ $totalPending }}</p>
            </div>

            {{-- Filter --}}
            <x-filter-bar :action="route('head.approvals.index')" :search-value="$search" search-placeholder="Cari nomor/pemohon/deskripsi..." :reset-url="route('head.approvals.index')">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Modul</label>
                    <select name="module" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Modul</option>
                        @foreach ($moduleLabels as $type => $label)
                            <option value="{{ $type }}" @selected($selectedModule === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Pengajuan</label>
                    <select name="category" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Jenis</option>
                        @foreach ($categoryLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Tanggal</p>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_from" value="{{ $dateFrom }}" placeholder="Dari"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="date" name="date_to" value="{{ $dateTo }}" placeholder="Sampai"
                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Urutkan</label>
                    <select name="sort" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="oldest" @selected($sort === 'oldest')>Terlama dulu</option>
                        <option value="newest" @selected($sort === 'newest')>Terbaru dulu</option>
                    </select>
                </div>
            </x-filter-bar>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Modul</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Ringkasan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Diajukan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Keputusan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $approval)
                                @php $approvable = $approval->approvable; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            {{ $moduleLabels[$approval->approvable_type] ?? class_basename($approval->approvable_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($approvable instanceof \App\Models\GA\GaRequest)
                                            <a href="{{ route('head.requests.show', $approvable) }}" class="text-indigo-600 hover:underline font-medium">
                                                {{ $approvable->request_number }}
                                            </a>
                                            <div class="text-xs text-gray-500">
                                                {{ $approvable->requester_name ?: ($approvable->requestedBy?->name ?? '—') }}
                                                &middot; {{ $approvable->branch?->name ?? '—' }}
                                                &middot; Rp {{ number_format((float) $approvable->total_amount, 0, ',', '.') }}
                                            </div>
                                        @elseif ($approvable instanceof \App\Models\Purchasing\PurchaseOrder)
                                            <span class="font-medium">{{ $approvable->po_number }}</span>
                                            <div class="text-xs text-gray-500">
                                                {{ $approvable->supplier?->name ?? '—' }}
                                                &middot; {{ $approvable->branch?->name ?? '—' }}
                                            </div>
                                        @else
                                            {{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $approval->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-2 min-w-[220px] @if ($approvable instanceof \App\Models\GA\GaRequest) sm:min-w-[280px] @endif">
                                            <form method="POST" action="{{ route('head.approvals.approve', $approval) }}" class="space-y-2">
                                                @csrf
                                                <div class="flex items-center gap-2">
                                                    <input type="text" name="note" placeholder="Catatan (opsional)"
                                                           class="flex-1 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <button type="submit"
                                                            onclick="return confirm('Setujui item ini?')"
                                                            class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
                                                        Approve
                                                    </button>
                                                </div>
                                                @if ($approvable instanceof \App\Models\GA\GaRequest)
                                                    <x-signature-pad name="signature"
                                                        :saved-signature-url="auth()->user()->signature_path ? \Illuminate\Support\Facades\Storage::url(auth()->user()->signature_path) : null"
                                                        label="Tanda Tangan" />
                                                @endif
                                            </form>
                                            <form method="POST" action="{{ route('head.approvals.reject', $approval) }}" class="flex items-center gap-2"
                                                  onsubmit="return confirm('Tolak item ini?')">
                                                @csrf
                                                <input type="text" name="note" placeholder="Alasan penolakan (wajib)" required
                                                       class="flex-1 rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-md hover:bg-red-100 whitespace-nowrap">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Tidak ada item yang menunggu approval Anda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $items->links() }}</div>
            </div>

            {{-- Riwayat approval yang sudah Anda putuskan --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Riwayat Approval Anda</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Modul</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Ringkasan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Keputusan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Catatan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($history as $approval)
                                @php $approvable = $approval->approvable; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                            {{ $moduleLabels[$approval->approvable_type] ?? class_basename($approval->approvable_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($approvable instanceof \App\Models\GA\GaRequest)
                                            <a href="{{ route('head.requests.show', $approvable) }}" class="text-indigo-600 hover:underline font-medium">
                                                {{ $approvable->request_number }}
                                            </a>
                                            <div class="text-xs text-gray-500">
                                                {{ $approvable->requester_name ?: ($approvable->requestedBy?->name ?? '—') }}
                                            </div>
                                        @elseif ($approvable instanceof \App\Models\Purchasing\PurchaseOrder)
                                            <span class="font-medium">{{ $approvable->po_number }}</span>
                                            <div class="text-xs text-gray-500">{{ $approvable->supplier?->name ?? '—' }}</div>
                                        @elseif ($approvable)
                                            {{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}
                                        @else
                                            <span class="text-gray-400">(data sudah dihapus)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $approval->status === 'approved' ? 'bg-status-approved-bg text-status-approved-fg' : 'bg-status-rejected-bg text-status-rejected-fg' }}">
                                            {{ ucfirst($approval->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $approval->note ?: '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ optional($approval->approved_at)->format('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat approval.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">{{ $history->links() }}</div>
            </div>

        </div>
    </div>
</x-app-layout>
