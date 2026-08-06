<x-app-layout sidebar="purchasing">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('purchasing.purchase-orders.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $purchaseOrder->po_number }}
            </h2>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $categoryLabels = \App\Models\Purchasing\PurchaseOrder::categoryLabels();
        $statusLabels = \App\Models\Purchasing\PurchaseOrder::statusLabels();
        $currentStep = $purchaseOrder->currentApprovalStep();
        // Sengaja cek hasRole(Finance) saja (bukan ditambah Role::ADMIN) —
        // Admin tidak bisa approve step ini kecuali benar-benar punya role
        // Finance juga, sama seperti Admin tidak bisa approve step
        // Head/Finance milik GaRequest (tidak ada konsep "Admin override").
        $canActOnFinanceStep = $currentStep
            && $currentStep->approver_role === \App\Models\Role::FINANCE
            && $user->hasRole(\App\Models\Role::FINANCE);
        // PO kategori 'general' punya step Purchasing (step 1) karena tidak
        // lewat Purchase Requisition — lihat PurchaseOrder::generateApprovalSteps().
        $canActOnPurchasingStep = $currentStep
            && $currentStep->approver_role === \App\Models\Role::PURCHASING
            && $user->hasRole(\App\Models\Role::PURCHASING);
        $canReceive = $purchaseOrder->status === \App\Models\Purchasing\PurchaseOrder::STATUS_APPROVED
            && ! $purchaseOrder->goodsReceipt
            && (
                $user->hasRole(\App\Models\Role::ADMIN)
                || ($user->hasRole(\App\Models\Role::GUDANG) && $purchaseOrder->branch_id === $user->branch_id)
                || ($user->hasRole(\App\Models\Role::GA) && $purchaseOrder->category === \App\Models\Purchasing\PurchaseOrder::CATEGORY_GENERAL)
            );
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
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\Purchasing\PurchaseOrder::statusBadgeColor($purchaseOrder->status) }}">
                        {{ $statusLabels[$purchaseOrder->status] ?? $purchaseOrder->status }}
                    </span>
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                        {{ $categoryLabels[$purchaseOrder->category] ?? $purchaseOrder->category }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Supplier</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseOrder->supplier?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tujuan</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseOrder->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Dipesan Oleh</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseOrder->orderedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal Pesan</dt>
                        <dd class="text-gray-800 font-medium">{{ $purchaseOrder->order_date->format('d M Y') }}</dd>
                    </div>
                    @if ($purchaseOrder->purchaseRequisition)
                        <div>
                            <dt class="text-gray-500">Dari Requisition</dt>
                            <dd class="text-gray-800 font-mono">
                                <a href="{{ route('purchasing.purchase-requisitions.show', $purchaseOrder->purchaseRequisition) }}" class="text-indigo-600 hover:underline">
                                    {{ $purchaseOrder->purchaseRequisition->requisition_number }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Daftar Barang</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Barang</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Satuan</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Harga/Unit</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($purchaseOrder->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800">{{ $item->item_name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->qty }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->unit }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-gray-800 font-medium">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right font-medium text-gray-600">Total</td>
                                <td class="px-4 py-3 text-right font-bold text-gray-800">Rp {{ number_format((float) $purchaseOrder->items->sum('subtotal'), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($purchaseOrder->approvals->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Alur Persetujuan</h3>
                    <x-approval-stepper :approvals="$purchaseOrder->approvals->sortBy('step')->values()" />
                    @if ($currentStep && $currentStep->approver_role === \App\Models\Role::HEAD)
                        <p class="text-xs text-gray-400 mt-3">Menunggu approval Head — diproses lewat Approval Inbox Head, bukan di halaman ini.</p>
                    @endif
                </div>
            @endif

            {{-- Approval Purchasing (step 1, PO kategori 'general' saja) — inline di sini. --}}
            @if ($canActOnPurchasingStep)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tindak Lanjut Approval (Purchasing)</h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <form method="POST" action="{{ route('purchasing.purchase-orders.approve', $purchaseOrder) }}" class="flex-1 flex items-center gap-2">
                            @csrf
                            <input type="text" name="note" placeholder="Catatan (opsional)"
                                   class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" onclick="return confirm('Setujui PO ini?')"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('purchasing.purchase-orders.reject', $purchaseOrder) }}" class="flex-1 flex items-center gap-2"
                              onsubmit="return confirm('Tolak PO ini?')">
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

            {{-- Approval Finance — inline di sini. Step Head diproses lewat Approval Inbox generik (head.approvals.*). --}}
            @if ($canActOnFinanceStep)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tindak Lanjut Approval (Finance)</h3>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <form method="POST" action="{{ route('purchasing.purchase-orders.approve', $purchaseOrder) }}" class="flex-1 flex items-center gap-2">
                            @csrf
                            <input type="text" name="note" placeholder="Catatan (opsional)"
                                   class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <button type="submit" onclick="return confirm('Setujui & cairkan dana utk PO ini?')"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 whitespace-nowrap">
                                Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('purchasing.purchase-orders.reject', $purchaseOrder) }}" class="flex-1 flex items-center gap-2"
                              onsubmit="return confirm('Tolak PO ini?')">
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

            {{-- Goods Receipt --}}
            @if ($purchaseOrder->goodsReceipt)
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-800">Penerimaan Barang</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <img src="{{ Storage::url($purchaseOrder->goodsReceipt->photo_path) }}" alt="Bukti terima"
                                 class="w-full sm:w-48 h-36 object-cover rounded-lg border border-gray-200">
                            <dl class="text-sm space-y-1">
                                <div><dt class="inline text-gray-500">Diterima oleh: </dt><dd class="inline text-gray-800 font-medium">{{ $purchaseOrder->goodsReceipt->receivedBy?->name ?? '—' }}</dd></div>
                                <div><dt class="inline text-gray-500">Tanggal: </dt><dd class="inline text-gray-800 font-medium">{{ $purchaseOrder->goodsReceipt->received_at->format('d M Y H:i') }}</dd></div>
                                @if ($purchaseOrder->goodsReceipt->notes)
                                    <div><dt class="inline text-gray-500">Catatan: </dt><dd class="inline text-gray-800">{{ $purchaseOrder->goodsReceipt->notes }}</dd></div>
                                @endif
                            </dl>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-500">Barang</th>
                                        <th class="px-4 py-2 text-right font-medium text-gray-500">Qty Diterima</th>
                                        @if ($purchaseOrder->category === \App\Models\Purchasing\PurchaseOrder::CATEGORY_FOOD)
                                            <th class="px-4 py-2 text-left font-medium text-gray-500">Kedaluwarsa</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($purchaseOrder->goodsReceipt->items as $receiptItem)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-800">{{ $receiptItem->purchaseOrderItem->item_name }}</td>
                                            <td class="px-4 py-2 text-right text-gray-600">{{ $receiptItem->qty_received }} {{ $receiptItem->purchaseOrderItem->unit }}</td>
                                            @if ($purchaseOrder->category === \App\Models\Purchasing\PurchaseOrder::CATEGORY_FOOD)
                                                <td class="px-4 py-2 text-gray-600">{{ $receiptItem->expiry_date?->format('d M Y') ?? '—' }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Invoice Supplier --}}
                @if ($user->hasRole(\App\Models\Role::FINANCE, \App\Models\Role::ADMIN))
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="font-medium text-gray-800 mb-3">Invoice Supplier</h3>

                        @if ($purchaseOrder->goodsReceipt->supplierInvoice)
                            @php $invoice = $purchaseOrder->goodsReceipt->supplierInvoice; @endphp
                            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-4">
                                <div>
                                    <dt class="text-gray-500">No. Invoice</dt>
                                    <dd class="text-gray-800 font-mono">{{ $invoice->invoice_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Jumlah</dt>
                                    <dd class="text-gray-800 font-medium">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Jatuh Tempo</dt>
                                    <dd class="text-gray-800 font-medium">{{ $invoice->due_date->format('d M Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Status</dt>
                                    <dd>
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ \App\Models\Purchasing\SupplierInvoice::statusBadgeColor($invoice->status) }}">
                                            {{ \App\Models\Purchasing\SupplierInvoice::statusLabels()[$invoice->status] }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                            <p class="text-xs text-gray-500 mb-3">Sudah dibayar: Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }} dari Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</p>

                            @if ($invoice->status !== \App\Models\Purchasing\SupplierInvoice::STATUS_PAID)
                                <form method="POST" action="{{ route('purchasing.supplier-invoices.payment', $invoice) }}" class="flex items-end gap-2">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Catat Pembayaran</label>
                                        <input type="number" name="amount_paid_now" min="0.01" step="0.01" required
                                               placeholder="Jumlah dibayar"
                                               class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 whitespace-nowrap">
                                        Simpan Pembayaran
                                    </button>
                                </form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('purchasing.goods-receipts.invoice.store', $purchaseOrder->goodsReceipt) }}"
                                  class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">No. Invoice *</label>
                                    <input type="text" name="invoice_number" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Jumlah (Rp) *</label>
                                    <input type="number" name="amount" min="0" step="0.01" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Jatuh Tempo *</label>
                                    <input type="date" name="due_date" required class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="sm:col-span-3">
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                        Catat Invoice
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            @elseif ($canReceive)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Konfirmasi Terima Barang</h3>
                    <form method="POST" action="{{ route('purchasing.purchase-orders.receipt.store', $purchaseOrder) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="space-y-2">
                            @foreach ($purchaseOrder->items as $item)
                                <div class="grid grid-cols-12 gap-2 items-center border border-gray-100 rounded-md p-3">
                                    <input type="hidden" name="items[{{ $loop->index }}][purchase_order_item_id]" value="{{ $item->id }}">
                                    <div class="col-span-12 sm:col-span-4 text-sm text-gray-800">{{ $item->item_name }} <span class="text-gray-400">({{ $item->qty }} {{ $item->unit }} dipesan)</span></div>
                                    <div class="col-span-6 sm:col-span-3">
                                        <input type="number" name="items[{{ $loop->index }}][qty_received]" min="0" value="{{ $item->qty }}" required
                                               placeholder="Qty diterima"
                                               class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    @if ($purchaseOrder->category === \App\Models\Purchasing\PurchaseOrder::CATEGORY_FOOD)
                                        <div class="col-span-6 sm:col-span-5">
                                            <input type="date" name="items[{{ $loop->index }}][expiry_date]" min="{{ now()->format('Y-m-d') }}"
                                                   class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti Terima *</label>
                            <input type="file" name="photo" accept="image/*" capture="environment" required
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Konfirmasi Terima
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div>
                <a href="{{ route('purchasing.purchase-orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke daftar</a>
            </div>
        </div>
    </div>
</x-app-layout>
