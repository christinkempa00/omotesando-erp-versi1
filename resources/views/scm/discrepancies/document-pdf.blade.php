<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #111827; }
        .header { display: table; width: 100%; margin-bottom: 8px; }
        .header .company { display: table-cell; font-weight: bold; font-size: 13px; vertical-align: top; }
        .header .address { display: table-cell; text-align: right; font-size: 10px; vertical-align: top; }
        hr { border: none; border-top: 1px solid #9ca3af; margin: 6px 0 14px 0; }
        .title { text-align: center; margin-bottom: 14px; }
        .title h1 { font-size: 14px; margin: 0; }

        table.main { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.main th, table.main td { border: 1px solid #9ca3af; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.main th { background: #f3f4f6; font-size: 10px; width: 30%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">PT. OMOTESANDO INDONESIA</div>
        <div class="address">
            Jl. Sunda No.11, RT.9/RW.4, Gondangdia, Kec. Menteng,<br>
            Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10350
        </div>
    </div>
    <hr>

    <div class="title">
        <h1>Laporan Selisih</h1>
    </div>

    <table class="main">
        <tr><th>Surat Jalan</th><td>{{ $report->deliveryNote?->delivery_code }}</td></tr>
        <tr><th>Dari</th><td>{{ $report->deliveryNote?->fromBranch?->name }}</td></tr>
        <tr><th>Ke</th><td>{{ $report->deliveryNote?->toBranch?->name }}</td></tr>
        <tr><th>Produk</th><td>{{ $report->deliveryNoteItem?->batchLabel?->productionBatchItem?->item_name }}</td></tr>
        <tr><th>Qty Ekspektasi (Dikirim)</th><td>{{ $report->qty_expected }}</td></tr>
        <tr><th>Qty Diterima</th><td>{{ $report->qty_received }}</td></tr>
        <tr><th>Selisih</th><td>{{ $report->qty_diff > 0 ? '+' : '' }}{{ $report->qty_diff }}</td></tr>
        <tr><th>Alasan</th><td>{{ $report->reason ?: '-' }}</td></tr>
        <tr><th>Tanggal</th><td>{{ $report->created_at->translatedFormat('l, d F Y H:i') }}</td></tr>
    </table>
</body>
</html>
