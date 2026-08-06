<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.subtitle { color: #6b7280; margin-top: 0; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
        .summary { display: table; width: 100%; margin-bottom: 16px; }
        .summary .col { display: table-cell; width: 25%; padding: 8px; border: 1px solid #d1d5db; }
        .summary .label { color: #6b7280; font-size: 10px; }
        .summary .value { font-size: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rekap Periodik SCM</h1>
    <p class="subtitle">
        Dicetak {{ now()->format('d/m/y H:i') }}
        @if ($dateFrom || $dateTo)
            &middot; Periode: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/y') : '...' }}
            s/d {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/y') : '...' }}
        @endif
    </p>

    <div class="summary">
        <div class="col"><div class="label">Total Pengiriman</div><div class="value">{{ $summary['total_deliveries'] }}</div></div>
        <div class="col"><div class="label">Total Qty Dikirim</div><div class="value">{{ $summary['total_qty_sent'] }}</div></div>
        <div class="col"><div class="label">Total Qty Diterima</div><div class="value">{{ $summary['total_qty_received'] }}</div></div>
        <div class="col"><div class="label">Ada Selisih</div><div class="value">{{ $summary['total_discrepancy'] }}</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Dari</th>
                <th>Ke</th>
                <th>Status</th>
                <th class="text-right">Qty Dikirim</th>
                <th class="text-right">Qty Diterima</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveryNotes as $deliveryNote)
                <tr>
                    <td>{{ $deliveryNote->delivery_code }}</td>
                    <td>{{ $deliveryNote->fromBranch?->name }}</td>
                    <td>{{ $deliveryNote->toBranch?->name }}</td>
                    <td>{{ \App\Models\SCM\DeliveryNote::statusLabels()[$deliveryNote->status] ?? $deliveryNote->status }}</td>
                    <td class="text-right">{{ $deliveryNote->items->sum('qty_sent') }}</td>
                    <td class="text-right">{{ $deliveryNote->items->sum('qty_received') }}</td>
                    <td>{{ $deliveryNote->created_at->format('d/m/y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
