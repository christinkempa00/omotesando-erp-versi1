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
        .title .no { font-size: 11px; margin-top: 2px; }

        .info { display: table; width: 100%; margin-bottom: 14px; }
        .info .col { display: table-cell; width: 50%; vertical-align: top; }
        .info .label { color: #6b7280; font-size: 10px; }
        .info .value { font-weight: bold; }

        table.main { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.main th, table.main td { border: 1px solid #9ca3af; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.main th { background: #f3f4f6; font-size: 10px; }
        .text-right { text-align: right; }
        .diff-note { color: #b45309; font-weight: bold; }

        h3.section { font-size: 12px; margin: 16px 0 6px 0; }

        .photos { display: table; width: 100%; margin-bottom: 16px; }
        .photos .col { display: table-cell; width: 50%; vertical-align: top; text-align: center; }
        .photos img { max-width: 160px; max-height: 160px; border: 1px solid #9ca3af; }
        .photos .caption { font-size: 10px; color: #6b7280; margin-top: 4px; }

        .signatures { display: table; width: 100%; margin-top: 30px; }
        .signatures .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .signatures .label { font-size: 10px; color: #4b5563; margin-bottom: 34px; }
        .signatures .name { font-size: 11px; font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 3px; }
        .signatures .role { font-size: 10px; color: #4b5563; }
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
        <h1>Berita Acara Serah Terima</h1>
        <div class="no">Ref. Surat Jalan : {{ $deliveryNote->delivery_code }}</div>
    </div>

    <div class="info">
        <div class="col">
            <div class="label">Dari</div>
            <div class="value">{{ $deliveryNote->fromBranch?->name }}</div>
        </div>
        <div class="col">
            <div class="label">Ke</div>
            <div class="value">{{ $deliveryNote->toBranch?->name }}</div>
        </div>
    </div>

    <table class="main">
        <thead>
            <tr>
                <th>Produk</th>
                <th style="width: 15%;">Qty Dikirim</th>
                <th style="width: 15%;">Qty Diterima</th>
                <th style="width: 15%;">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryNote->items as $item)
                <tr>
                    <td>{{ $item->batchLabel?->productionBatchItem?->item_name }}</td>
                    <td class="text-right">{{ $item->qty_sent }}</td>
                    <td class="text-right">{{ $item->qty_received ?? '-' }}</td>
                    <td class="text-right @if($item->discrepancy) diff-note @endif">
                        {{ $item->discrepancy ? ($item->discrepancy->qty_diff > 0 ? '+' : '').$item->discrepancy->qty_diff : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section">Dokumentasi Foto</h3>
    <div class="photos">
        <div class="col">
            @if ($deliveryNote->sent_photo_path)
                <img src="{{ public_path('storage/'.$deliveryNote->sent_photo_path) }}">
            @endif
            <div class="caption">Foto Saat Kirim</div>
        </div>
        <div class="col">
            @if ($deliveryNote->receipt?->received_photo_path)
                <img src="{{ public_path('storage/'.$deliveryNote->receipt->received_photo_path) }}">
            @endif
            <div class="caption">Foto Saat Terima</div>
        </div>
    </div>

    @if ($deliveryNote->receipt?->notes)
        <p><strong>Catatan:</strong> {{ $deliveryNote->receipt->notes }}</p>
    @endif

    <div class="signatures">
        <div class="col">
            <div class="label">Diserahkan oleh</div>
            <div class="name">{{ $deliveryNote->sentBy?->name ?: '-' }}</div>
            <div class="role">Gudang · {{ optional($deliveryNote->sent_at)->format('d M Y H:i') }}</div>
        </div>
        <div class="col">
            <div class="label">Diterima oleh</div>
            <div class="name">{{ $deliveryNote->receipt?->receivedBy?->name ?: '-' }}</div>
            <div class="role">Outlet · {{ optional($deliveryNote->receipt?->received_at)->format('d M Y H:i') }}</div>
        </div>
    </div>
</body>
</html>
