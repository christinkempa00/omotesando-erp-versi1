<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <x-back-link :href="route('outlet.requests.index')" parent="Asset Request" current="Detail Pengajuan" />
                <h2 class="font-semibold text-xl text-ink leading-tight">
                    Pengajuan {{ $gaRequest->request_number }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                @if ($gaRequest->status === \App\Models\GA\GaRequest::STATUS_DRAFT && $gaRequest->requested_by === auth()->id() && auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_REQUESTS))
                    <a href="{{ route('outlet.requests.edit', $gaRequest) }}"
                       class="inline-flex items-center gap-2 btn-primary">
                        Lanjutkan Draft
                    </a>
                @endif
                <a href="{{ route('outlet.requests.document', $gaRequest) }}"
                   class="inline-flex items-center gap-2 btn-secondary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6" />
                    </svg>
                    Dokumen (PDF)
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card-flat p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($gaRequest->status) }}">
                        {{ \App\Models\GA\GaRequest::statusLabels()[$gaRequest->status] ?? $gaRequest->status }}
                    </span>
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\GaRequest::priorityBadgeColor($gaRequest->priority) }}">
                        {{ $priorityLabels[$gaRequest->priority] ?? $gaRequest->priority }}
                    </span>
                    <span class="text-sm text-ink-muted">
                        {{ $categoryLabels[$gaRequest->category] ?? $gaRequest->category }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-ink-muted">Nomor</dt>
                        <dd class="text-ink font-mono">{{ $gaRequest->request_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Pemohon</dt>
                        <dd class="text-ink font-medium">{{ $gaRequest->requester_name ?: ($gaRequest->requestedBy?->name ?? '—') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-ink-muted">Deskripsi</dt>
                        <dd class="text-ink whitespace-pre-line">{{ $gaRequest->description }}</dd>
                    </div>
                </dl>
            </div>

            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline">
                    <h3 class="font-medium text-ink">Daftar Item</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[32%]">Item</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[10%]">Qty</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[12%]">Satuan</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[16%]">Harga Satuan</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[15%]">Vendor</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[15%]">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @foreach ($gaRequest->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-ink">{{ $item->item_name }}{{ $item->type ? ' - '.$item->type : '' }}</td>
                                    <td class="px-4 py-3 text-right text-ink-muted">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $item->unit ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right text-ink-muted">Rp {{ number_format((float) $item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $item->vendor_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-ink font-medium">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-surface">
                                <td colspan="5" class="px-4 py-3 text-right text-ink-muted">Sub Total</td>
                                <td class="px-4 py-3 text-right text-ink">Rp {{ number_format((float) $gaRequest->subtotal_amount, 0, ',', '.') }}</td>
                            </tr>
                            @if ($gaRequest->discount_percent)
                                <tr class="bg-surface">
                                    <td colspan="5" class="px-4 py-3 text-right text-ink-muted">Diskon ({{ \App\Models\GA\GaRequest::formatPercent($gaRequest->discount_percent) }}%)</td>
                                    <td class="px-4 py-3 text-right text-ink">&minus; Rp {{ number_format($gaRequest->discountAmount(), 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if ($gaRequest->pph_percent)
                                <tr class="bg-surface">
                                    <td colspan="5" class="px-4 py-3 text-right text-ink-muted">PPH ({{ \App\Models\GA\GaRequest::formatPercent($gaRequest->pph_percent) }}%)</td>
                                    <td class="px-4 py-3 text-right text-ink">&minus; Rp {{ number_format($gaRequest->pphAmount(), 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="bg-surface">
                                <td colspan="5" class="px-4 py-3 text-right font-medium text-ink">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-ink">Rp {{ number_format((float) $gaRequest->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($gaRequest->attachments->isNotEmpty())
                <div class="card-flat p-6">
                    <h3 class="font-medium text-ink mb-3">Lampiran Foto</h3>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                        @foreach ($gaRequest->attachments as $attachment)
                            <a href="{{ Storage::url($attachment->photo_path) }}" target="_blank">
                                <img src="{{ Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-md object-cover border border-hairline">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <a href="{{ route('outlet.requests.index') }}" class="text-sm text-ink-muted hover:text-ink">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
