<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <x-back-link :href="route('ga.requests.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Pengajuan {{ $gaRequest->request_number }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                @if ($gaRequest->status === \App\Models\GA\GaRequest::STATUS_DRAFT && $gaRequest->requested_by === auth()->id())
                    <a href="{{ route('ga.requests.edit', $gaRequest) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Lanjutkan Draft
                    </a>
                @endif
                <a href="{{ route('ga.requests.document', $gaRequest) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
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

            {{-- Ringkasan --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($gaRequest->status) }}">
                        {{ \App\Models\GA\GaRequest::statusLabels()[$gaRequest->status] ?? $gaRequest->status }}
                    </span>
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\GaRequest::priorityBadgeColor($gaRequest->priority) }}">
                        {{ $priorityLabels[$gaRequest->priority] ?? $gaRequest->priority }}
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ $categoryLabels[$gaRequest->category] ?? $gaRequest->category }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Nomor</dt>
                        <dd class="text-gray-800 font-mono">{{ $gaRequest->request_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Outlet</dt>
                        <dd class="text-gray-800 font-medium">{{ $gaRequest->outletLabel() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Divisi</dt>
                        <dd class="text-gray-800 font-medium">{{ $gaRequest->division?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Pemohon</dt>
                        <dd class="text-gray-800 font-medium">{{ $gaRequest->requester_name ?: ($gaRequest->requestedBy?->name ?? '—') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Deskripsi</dt>
                        <dd class="text-gray-800 whitespace-pre-line">{{ $gaRequest->description }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Item --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Daftar Item</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-[32%]">Item</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 w-[10%]">Qty</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-[12%]">Satuan</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 w-[16%]">Harga Satuan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-[15%]">Vendor</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 w-[15%]">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($gaRequest->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->item_name }}{{ $item->type ? ' - '.$item->type : '' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->unit ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format((float) $item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->vendor_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 font-medium">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="5" class="px-4 py-3 text-right text-gray-600">Sub Total</td>
                                <td class="px-4 py-3 text-right text-gray-800">Rp {{ number_format((float) $gaRequest->subtotal_amount, 0, ',', '.') }}</td>
                            </tr>
                            @if ($gaRequest->discount_percent)
                                <tr class="bg-gray-50">
                                    <td colspan="5" class="px-4 py-3 text-right text-gray-600">Diskon ({{ \App\Models\GA\GaRequest::formatPercent($gaRequest->discount_percent) }}%)</td>
                                    <td class="px-4 py-3 text-right text-gray-800">&minus; Rp {{ number_format($gaRequest->discountAmount(), 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if ($gaRequest->pph_percent)
                                <tr class="bg-gray-50">
                                    <td colspan="5" class="px-4 py-3 text-right text-gray-600">PPH ({{ \App\Models\GA\GaRequest::formatPercent($gaRequest->pph_percent) }}%)</td>
                                    <td class="px-4 py-3 text-right text-gray-800">&minus; Rp {{ number_format($gaRequest->pphAmount(), 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="bg-gray-50">
                                <td colspan="5" class="px-4 py-3 text-right font-medium text-gray-700">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-800">Rp {{ number_format((float) $gaRequest->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Lampiran foto --}}
            @if ($gaRequest->attachments->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Lampiran Foto</h3>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                        @foreach ($gaRequest->attachments as $attachment)
                            <a href="{{ Storage::url($attachment->photo_path) }}" target="_blank">
                                <img src="{{ Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-md object-cover border border-gray-200">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <a href="{{ route('ga.requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
