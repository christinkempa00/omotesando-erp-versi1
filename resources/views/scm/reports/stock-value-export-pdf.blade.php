<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        p.subtitle { color: #6b7280; margin-top: 0; margin-bottom: 4px; }
        p.total { font-size: 13px; font-weight: bold; margin-top: 0; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Nilai Persediaan</h1>
    <p class="subtitle">Dicetak {{ now()->format('d/m/y H:i') }}</p>
    <p class="total">Total Nilai Persediaan: Rp {{ number_format($grandTotal, 0, ',', '.') }}</p>

    <table>
        <thead>
            <tr>
                <th>Outlet</th>
                <th>Label</th>
                <th>Produk</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Biaya/Unit</th>
                <th class="text-right">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['branch_name'] ?: '-' }}</td>
                    <td>{{ $row['label_code'] }}</td>
                    <td>{{ $row['item_name'] }} ({{ $row['unit'] }})</td>
                    <td class="text-right">{{ $row['qty_on_hand'] }}</td>
                    <td class="text-right">{{ $row['unit_cost'] !== null ? 'Rp '.number_format($row['unit_cost'], 0, ',', '.') : 'Belum ada harga' }}</td>
                    <td class="text-right">{{ $row['value'] !== null ? 'Rp '.number_format($row['value'], 0, ',', '.') : '-' }}</td>
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
