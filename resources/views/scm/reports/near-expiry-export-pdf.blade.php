<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.subtitle { color: #6b7280; margin-top: 0; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Stok Mendekati Kedaluwarsa</h1>
    <p class="subtitle">Ambang batas {{ $days }} hari &middot; Dicetak {{ now()->format('d/m/y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Outlet</th>
                <th>Label</th>
                <th>Produk</th>
                <th class="text-right">Qty</th>
                <th>Kedaluwarsa</th>
                <th class="text-right">Sisa Hari</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($balances as $balance)
                @php
                    $stockable = $balance->stockable;
                    $isBatchLabel = $stockable instanceof \App\Models\SCM\BatchLabel;
                    $item = $isBatchLabel ? $stockable->productionBatchItem : $stockable->purchaseOrderItem;
                    $code = $isBatchLabel ? $stockable->label_code : $stockable->purchaseOrderItem->purchaseOrder->po_number;
                @endphp
                <tr>
                    <td>{{ $balance->branch?->name ?: '-' }}</td>
                    <td>{{ $code }}</td>
                    <td>{{ $item->item_name }} ({{ $item->unit }})</td>
                    <td class="text-right">{{ $balance->qty_on_hand }}</td>
                    <td>{{ $stockable->expiry_date->format('d/m/Y') }}</td>
                    <td class="text-right">{{ $stockable->daysUntilExpiry() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#9ca3af;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
