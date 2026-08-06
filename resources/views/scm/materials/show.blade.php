<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('scm.materials.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Pengajuan {{ $materialRequest->request_number }}
                </h2>
            </div>
            <a href="{{ route('scm.materials.document', $materialRequest) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6" />
                </svg>
                Bukti Pengambilan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\SCM\MaterialRequest::statusBadgeColor($materialRequest->status) }}">
                        {{ \App\Models\SCM\MaterialRequest::statusLabels()[$materialRequest->status] ?? $materialRequest->status }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Nomor</dt>
                        <dd class="text-gray-800 font-mono">{{ $materialRequest->request_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Outlet</dt>
                        <dd class="text-gray-800 font-medium">{{ $materialRequest->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Pemohon</dt>
                        <dd class="text-gray-800 font-medium">{{ $materialRequest->requestedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal</dt>
                        <dd class="text-gray-800 font-medium">{{ $materialRequest->created_at->format('d M Y') }}</dd>
                    </div>
                    @if ($materialRequest->description)
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Deskripsi</dt>
                            <dd class="text-gray-800 whitespace-pre-line">{{ $materialRequest->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Daftar Bahan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Bahan</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($materialRequest->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->item_name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($materialRequest->approvals->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Alur Persetujuan</h3>
                    <x-approval-stepper :approvals="$materialRequest->approvals->sortBy('step')->values()" />
                </div>
            @endif

            {{-- Approval — Admin saja, hanya kalau masih ada step pending --}}
            @if (auth()->user()->hasRole(\App\Models\Role::ADMIN) && $materialRequest->currentApprovalStep())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tindak Lanjut Approval</h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <form method="POST" action="{{ route('scm.materials.approve', $materialRequest) }}" class="flex-1 flex items-center gap-2">
                            @csrf
                            <input type="text" name="note" placeholder="Catatan (opsional)"
                                   class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" onclick="return confirm('Setujui pengajuan ini?')"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('scm.materials.reject', $materialRequest) }}" class="flex-1 flex items-center gap-2"
                              onsubmit="return confirm('Tolak pengajuan ini?')">
                            @csrf
                            <input type="text" name="note" placeholder="Alasan penolakan (wajib)" required
                                   class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100 whitespace-nowrap">
                                Tolak
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($materialRequest->status === \App\Models\SCM\MaterialRequest::STATUS_APPROVED && $materialRequest->productionBatches->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-800">Batch Produksi Terkait</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($materialRequest->productionBatches as $batch)
                            <li class="px-6 py-3 flex items-center justify-between">
                                <a href="{{ route('scm.batches.show', $batch) }}" class="font-mono text-indigo-600 hover:underline">{{ $batch->batch_number }}</a>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\SCM\ProductionBatch::statusBadgeColor($batch->status) }}">
                                    {{ \App\Models\SCM\ProductionBatch::statusLabels()[$batch->status] ?? $batch->status }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <a href="{{ route('scm.materials.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
