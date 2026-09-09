<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Pengajuan {{ $gaRequest->request_number }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

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

            {{-- Ringkasan --}}
            <div class="card-flat p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($gaRequest->status) }}">
                        {{ \App\Models\GA\GaRequest::statusLabels()[$gaRequest->status] ?? $gaRequest->status }}
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
                        <dt class="text-ink-muted">Outlet</dt>
                        <dd class="text-ink font-medium">{{ $gaRequest->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Divisi</dt>
                        <dd class="text-ink font-medium">{{ $gaRequest->division?->name ?? '—' }}</dd>
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

            {{-- Item --}}
            <div class="card-flat overflow-hidden">
                <div class="px-6 py-4 border-b border-hairline">
                    <h3 class="font-medium text-ink">Daftar Item</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[40%]">Item</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[12%]">Qty</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[18%]">Harga Satuan</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[15%]">Vendor</th>
                                <th class="px-4 py-3 text-left text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase w-[15%]">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @foreach ($gaRequest->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-ink">{{ $item->item_name }}{{ $item->type ? ' - '.$item->type : '' }}</td>
                                    <td class="px-4 py-3 text-right text-ink-muted">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-right text-ink-muted">Rp {{ number_format((float) $item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $item->vendor_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-ink font-medium">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-surface">
                                <td colspan="4" class="px-4 py-3 text-right font-medium text-ink">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-ink">Rp {{ number_format((float) $gaRequest->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div>
                <x-back-link :href="route('head.dashboard')" parent="Dashboard" current="Detail Pengajuan" />
            </div>
        </div>
    </div>
</x-app-layout>
