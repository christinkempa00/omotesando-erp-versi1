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
    </style>
</head>
<body>
    <h1>Batch Produksi</h1>
    <p class="subtitle">Dicetak {{ now()->format('d/m/y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Nomor Batch</th>
                <th>Dari Pengajuan</th>
                <th>Dibuat Oleh</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($batches as $batch)
                <tr>
                    <td>{{ $batch->batch_number }}</td>
                    <td>{{ $batch->materialRequest?->request_number ?: '-' }}</td>
                    <td>{{ $batch->producedBy?->name ?: '-' }}</td>
                    <td>{{ $statusLabels[$batch->status] ?? $batch->status }}</td>
                    <td>{{ $batch->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
