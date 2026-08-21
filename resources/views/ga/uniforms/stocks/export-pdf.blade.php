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
    <h1>Inventaris Seragam</h1>
    <p class="subtitle">Dicetak {{ now()->format('d/m/y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tipe</th>
                <th>Ukuran</th>
                <th>Warna</th>
                <th>Outlet</th>
                <th>Kondisi</th>
                <th class="text-right">Tersedia</th>
                <th class="text-right">Tidak Layak</th>
                <th class="text-right">Ambang Low Stock</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td>{{ $stock->stock_code }}</td>
                    <td>{{ $stock->uniform_type }}</td>
                    <td>{{ $stock->size ?: '-' }}</td>
                    <td>{{ $stock->color ?: '-' }}</td>
                    <td>{{ $stock->branch?->name ?: '-' }}</td>
                    <td>{{ $statusLabels[$stock->status] ?? $stock->status }}</td>
                    <td class="text-right">{{ $stock->available_stock }}</td>
                    <td class="text-right">{{ $stock->unusable_stock }}</td>
                    <td class="text-right">{{ $stock->low_stock_threshold }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:#9ca3af;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
