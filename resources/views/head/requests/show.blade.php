<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\GaRequest::statusBadgeColor($gaRequest->status) }}">
                        {{ \App\Models\GA\GaRequest::statusLabels()[$gaRequest->status] ?? $gaRequest->status }}
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
                        <dd class="text-gray-800 font-medium">{{ $gaRequest->branch?->name ?? '—' }}</dd>
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
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-[40%]">Item</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 w-[12%]">Qty</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 w-[18%]">Harga Satuan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 w-[15%]">Vendor</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 w-[15%]">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($gaRequest->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->item_name }}{{ $item->type ? ' - '.$item->type : '' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format((float) $item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->vendor_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 font-medium">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-4 py-3 text-right font-medium text-gray-700">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-800">Rp {{ number_format((float) $gaRequest->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Langkah approval (read-only) --}}
            @if ($gaRequest->approvals->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-800">Alur Persetujuan</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($gaRequest->approvals->sortBy('step') as $approval)
                            <li class="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-700">Step {{ $approval->step }}</span>
                                    <span class="text-gray-500 ml-2">{{ $approval->approver_role }}</span>
                                    @if ($approval->approver)
                                        <span class="text-gray-400 ml-1">· {{ $approval->approver->name }}</span>
                                    @endif
                                    @if ($approval->note)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $approval->note }}</div>
                                    @endif
                                </div>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                    @class([
                                        'bg-green-100 text-green-700' => $approval->status === 'approved',
                                        'bg-red-100 text-red-700' => $approval->status === 'rejected',
                                        'bg-gray-100 text-gray-600' => ! in_array($approval->status, ['approved', 'rejected']),
                                    ])">
                                    {{ ucfirst($approval->status) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Aksi approve/reject, hanya kalau giliran Head sekarang --}}
            @php
                $currentStep = $gaRequest->currentApprovalStep();
                $savedSignatureUrl = auth()->user()->signature_path ? \Illuminate\Support\Facades\Storage::url(auth()->user()->signature_path) : null;
            @endphp
            @if ($currentStep && $currentStep->approver_role === \App\Models\Role::HEAD)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Keputusan Anda</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <form method="POST" action="{{ route('head.requests.approve', $gaRequest) }}" class="space-y-3">
                            @csrf
                            <input type="text" name="note" placeholder="Catatan (opsional)"
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-signature-pad name="signature" :saved-signature-url="$savedSignatureUrl" label="Tanda Tangan Approve" />
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('head.requests.reject', $gaRequest) }}" class="space-y-3"
                              onsubmit="return confirm('Tolak pengajuan {{ $gaRequest->request_number }}?')">
                            @csrf
                            <input type="text" name="note" placeholder="Alasan penolakan (wajib)" required
                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" class="w-full px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div>
                <a href="{{ route('head.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke dashboard</a>
            </div>
        </div>
    </div>
</x-app-layout>
