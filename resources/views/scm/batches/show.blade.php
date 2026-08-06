<x-app-layout sidebar="scm">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('scm.batches.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Batch {{ $productionBatch->batch_number }}
                </h2>
            </div>
            @if ($productionBatch->status === \App\Models\SCM\ProductionBatch::STATUS_APPROVED && auth()->user()->hasRole(\App\Models\Role::GUDANG, \App\Models\Role::ADMIN))
                <form method="POST" action="{{ route('scm.batches.labels.store', $productionBatch) }}" class="flex items-end gap-2">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Tanggal Kedaluwarsa (opsional)</label>
                        <input type="date" name="expiry_date" min="{{ now()->format('Y-m-d') }}"
                               class="rounded-md border-gray-300 text-sm shadow-sm focus:border-accent focus:ring-accent">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 whitespace-nowrap">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="7" width="18" height="14" rx="1.5" /><path d="M8 3h8v4H8z" />
                        </svg>
                        Cetak Label
                    </button>
                </form>
            @endif
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
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\SCM\ProductionBatch::statusBadgeColor($productionBatch->status) }}">
                        {{ \App\Models\SCM\ProductionBatch::statusLabels()[$productionBatch->status] ?? $productionBatch->status }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Dari Pengajuan</dt>
                        <dd class="text-gray-800 font-mono">
                            <a href="{{ route('scm.materials.show', $productionBatch->materialRequest) }}" class="text-indigo-600 hover:underline">
                                {{ $productionBatch->materialRequest?->request_number }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dibuat Oleh</dt>
                        <dd class="text-gray-800 font-medium">{{ $productionBatch->producedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Waktu Produksi</dt>
                        <dd class="text-gray-800 font-medium">{{ optional($productionBatch->produced_at)->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Produk Hasil Batch</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Produk</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Satuan</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Biaya/Unit</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Label</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($productionBatch->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->item_name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->unit }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->unit_cost !== null ? 'Rp '.number_format((float) $item->unit_cost, 0, ',', '.') : '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($item->labels->isNotEmpty())
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                {{ $item->labels->first()->label_code }}
                                            </span>
                                            @if ($item->labels->first()->expiry_date)
                                                <span class="ml-1 text-xs {{ $item->labels->first()->isNearExpiry() ? 'text-status-discrepancy-fg font-semibold' : 'text-gray-400' }}">
                                                    ED {{ $item->labels->first()->expiry_date->format('d M Y') }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">Belum dicetak</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($productionBatch->items->flatMap->labels->isNotEmpty())
                    <div class="px-6 py-3 border-t border-gray-100">
                        <a href="{{ route('scm.batches.labels.print', $productionBatch) }}" class="text-sm text-indigo-600 hover:underline">
                            Lihat / Cetak Semua Label &rarr;
                        </a>
                    </div>
                @endif
            </div>

            @if ($productionBatch->approvals->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Alur Persetujuan</h3>
                    <x-approval-stepper :approvals="$productionBatch->approvals->sortBy('step')->values()" />
                </div>
            @endif

            {{-- Approval — Admin saja, hanya kalau masih ada step pending --}}
            @if (auth()->user()->hasRole(\App\Models\Role::ADMIN) && $productionBatch->currentApprovalStep())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tindak Lanjut Approval</h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <form method="POST" action="{{ route('scm.batches.approve', $productionBatch) }}" class="flex-1 flex items-center gap-2">
                            @csrf
                            <input type="text" name="note" placeholder="Catatan (opsional)"
                                   class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" onclick="return confirm('Setujui batch ini?')"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('scm.batches.reject', $productionBatch) }}" class="flex-1 flex items-center gap-2"
                              onsubmit="return confirm('Tolak batch ini?')">
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

            <div>
                <a href="{{ route('scm.batches.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
