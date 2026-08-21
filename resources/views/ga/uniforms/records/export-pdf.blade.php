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
    <h1>Serah Terima Seragam</h1>
    <p class="subtitle">Dicetak {{ now()->format('d/m/y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Karyawan</th>
                <th>Item</th>
                <th>Outlet</th>
                <th>Tgl Serah</th>
                <th>Status</th>
                <th>Tgl Kembali</th>
                <th>Kondisi Kembali</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $record->record_code }}</td>
                    <td>{{ $record->employee_name }}</td>
                    <td>{{ $record->summaryLabel() }}</td>
                    <td>{{ $record->branch?->name ?: '-' }}</td>
                    <td>{{ $record->issue_date?->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ $statusLabels[$record->status] ?? $record->status }}</td>
                    <td>{{ $record->return_date?->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ $record->return_condition ? ($conditionLabels[$record->return_condition] ?? $record->return_condition) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#9ca3af;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
