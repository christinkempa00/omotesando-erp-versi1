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
    <h1>Inventaris Aset</h1>
    <p class="subtitle">
        Dicetak {{ now()->format('d/m/y H:i') }}
        @if ($dateFrom || $dateTo)
            &middot; Periode Tanggal Beli: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/y') : '...' }}
            s/d {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/y') : '...' }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>ID Aset</th>
                <th>Nama Aset</th>
                <th>Merk</th>
                <th>Outlet</th>
                <th>Lokasi</th>
                <th>Tanggal Beli</th>
                <th>Status</th>
                <th>Penanggung Jawab</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assets as $asset)
                <tr>
                    <td>{{ $asset->asset_code }}</td>
                    <td>{{ $asset->name }}</td>
                    <td>{{ $asset->brand ?: '-' }}</td>
                    <td>{{ $asset->outletLabel() ?: '-' }}</td>
                    <td>{{ $asset->location ?: '-' }}</td>
                    <td>{{ optional($asset->purchase_date)->format('d/m/y') ?: '-' }}</td>
                    <td>{{ $statusLabels[$asset->status] ?? $asset->status }}</td>
                    <td>{{ $asset->custodian_name ?: '-' }}</td>
                    <td class="text-right">{{ $asset->quantity }}</td>
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
