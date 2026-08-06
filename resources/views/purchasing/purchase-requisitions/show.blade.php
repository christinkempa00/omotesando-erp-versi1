<x-app-layout sidebar="purchasing">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('purchasing.purchase-requisitions.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $purchaseRequisition->requisition_number }}
            </h2>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $currentStep = $purchaseRequisition->currentApprovalStep();
        $canAct = $currentStep && $user->hasRole(\App\Models\Role::PURCHASING);
        $canCreatePo = $purchaseRequisition->status === \App\Models\Purchasing\PurchaseRequisition::STATUS_APPROVED
            && $purchaseRequisition->purchaseOrders->isEmpty()
            && $user->hasRole(\App\Models\Role::PURCHASING, \App\Models\Role::ADMIN);
    @endphp

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
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\Purchasing\PurchaseRequisition::statusBadgeColor($purchaseRequisition->status) }}">
                        {{ \App\Models\Purchasing\PurchaseRequisition::statusLabels()[$purchaseRequisition->status] ?? $purchaseRequisition->status }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Outlet</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseRequisition->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Pemohon</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseRequisition->requestedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseRequisition->created_at->format('d M Y') }}</dd>
                    </div>
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
                            @foreach ($purchaseRequisition->items as $item)
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

            @if ($purchaseRequisition->approvals->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Alur Persetujuan</h3>
                    <x-approval-stepper :approvals="$purchaseRequisition->approvals->sortBy('step')->values()" />
                </div>
            @endif

            {{-- Approval — Purchasing saja, hanya kalau masih ada step pending --}}
            @if ($canAct)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tindak Lanjut Approval</h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <form method="POST" action="{{ route('purchasing.purchase-requisitions.approve', $purchaseRequisition) }}" class="flex-1 flex items-center gap-2">
                            @csrf
                            <input type="text" name="note" placeholder="Catatan (opsional)"
                                   class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" onclick="return confirm('Setujui requisition ini?')"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('purchasing.purchase-requisitions.reject', $purchaseRequisition) }}" class="flex-1 flex items-center gap-2"
                              onsubmit="return confirm('Tolak requisition ini?')">
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

            @if ($canCreatePo)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Buat Purchase Order</h3>
                    <p class="text-sm text-gray-500 mb-3">Requisition ini sudah disetujui — buat PO-nya (pilih supplier & isi harga per item).</p>
                    <a href="{{ route('purchasing.purchase-orders.create-from-requisition', $purchaseRequisition) }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Buat PO dari Requisition Ini
                    </a>
                </div>
            @endif

            @if ($purchaseRequisition->purchaseOrders->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-800">Purchase Order Terkait</h3>
                    </div>
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach ($purchaseRequisition->purchaseOrders as $po)
                            <li class="px-6 py-3 flex items-center justify-between">
                                <a href="{{ route('purchasing.purchase-orders.show', $po) }}" class="font-mono text-indigo-600 hover:underline">{{ $po->po_number }}</a>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\Purchasing\PurchaseOrder::statusBadgeColor($po->status) }}">
                                    {{ \App\Models\Purchasing\PurchaseOrder::statusLabels()[$po->status] ?? $po->status }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <a href="{{ route('purchasing.purchase-requisitions.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
