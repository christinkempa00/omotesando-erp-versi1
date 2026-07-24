<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Pengajuan {{ $gaRequest->request_number }}
            </h2>
            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\GaRequest::statusBadgeColor($gaRequest->status) }}">
                {{ \App\Models\GA\GaRequest::statusLabels()[$gaRequest->status] }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Info umum --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Pemohon</dt>
                        <dd class="font-medium text-gray-900">{{ $gaRequest->requestedBy->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Divisi</dt>
                        <dd class="font-medium text-gray-900">{{ $gaRequest->division->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Cabang</dt>
                        <dd class="font-medium text-gray-900">{{ $gaRequest->branch->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Kategori</dt>
                        <dd class="font-medium text-gray-900">{{ $categoryLabels[$gaRequest->category] }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Keterangan</dt>
                        <dd class="font-medium text-gray-900">{{ $gaRequest->description }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal Pengajuan</dt>
                        <dd class="font-medium text-gray-900">{{ $gaRequest->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Item --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Daftar Item</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Nama Item</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Qty</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Harga Satuan</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Vendor</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gaRequest->items as $item)
                                <tr class="border-t">
                                    <td class="px-3 py-2">{{ $item->item_name }}</td>
                                    <td class="px-3 py-2">{{ $item->qty }}</td>
                                    <td class="px-3 py-2">Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2">{{ $item->vendor_name ?: '-' }}</td>
                                    <td class="px-3 py-2">Rp {{ number_format($item->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right font-medium text-gray-700">Total</td>
                                <td class="px-3 py-2 font-semibold text-gray-900">
                                    Rp {{ number_format($gaRequest->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Timeline approval (read-only untuk tahap ini) --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Status Approval</h3>
                <ol class="relative border-s-2 border-gray-200 ms-3 space-y-6">
                    @foreach ($gaRequest->approvals as $approval)
                        <li class="ms-6">
                            <span @class([
                                'absolute flex items-center justify-center w-6 h-6 rounded-full -start-3 ring-4 ring-white text-white text-xs font-bold',
                                'bg-green-500' => $approval->status === 'approved',
                                'bg-red-500' => $approval->status === 'rejected',
                                'bg-gray-300' => $approval->status === 'pending',
                            ])>
                                {{ $approval->step }}
                            </span>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $approval->approver_role }}
                                <span class="ms-2 text-xs font-normal px-2 py-0.5 rounded-full {{ \App\Models\GA\GaRequest::statusBadgeColor($approval->status === 'approved' ? 'approved' : ($approval->status === 'rejected' ? 'rejected' : 'draft')) }}">
                                    {{ ucfirst($approval->status) }}
                                </span>
                            </p>
                            @if ($approval->approver)
                                <p class="text-xs text-gray-500">oleh {{ $approval->approver->name }}</p>
                            @endif
                            @if ($approval->note)
                                <p class="text-xs text-gray-500 mt-1">Catatan: {{ $approval->note }}</p>
                            @endif
                            @if ($approval->approved_at)
                                <p class="text-xs text-gray-400 mt-1">{{ $approval->approved_at->format('d M Y, H:i') }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
                <p class="text-xs text-gray-400 mt-4">
                    Catatan: aksi approve/reject akan tersedia di modul Finance & Head (belum dibangun di tahap ini).
                </p>
            </div>

            <div>
                <a href="{{ route('ga.requests.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    &larr; Kembali ke daftar
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
