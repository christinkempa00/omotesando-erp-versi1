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

        .qr { text-align: center; margin-bottom: 14px; }
        .qr img { width: 90px; height: 90px; }

        table.main { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.main th, table.main td { border: 1px solid #9ca3af; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.main th { background: #f3f4f6; font-size: 10px; }
        .text-right { text-align: right; }

        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signatures .col { display: table-cell; width: 33%; vertical-align: top; padding-right: 10px; }
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
        <h1>Surat Jalan</h1>
        <div class="no">No : {{ $deliveryNote->delivery_code }}</div>
    </div>

    @if ($deliveryNote->qr_code)
        <div class="qr">
            <img src="data:image/svg+xml;base64,{{ base64_encode($deliveryNote->qr_code) }}">
        </div>
    @endif

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
                <th style="width: 6%;">No</th>
                <th style="width: 20%;">Kode Label</th>
                <th>Produk</th>
                <th style="width: 15%;">Qty Dikirim</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryNote->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->batchLabel?->label_code }}</td>
                    <td>{{ $item->batchLabel?->productionBatchItem?->item_name }}</td>
                    <td class="text-right">{{ $item->qty_sent }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="col">
            <div class="label">Dikirim oleh</div>
            <div class="name">{{ $deliveryNote->sentBy?->name ?: '-' }}</div>
            <div class="role">Gudang</div>
        </div>
        <div class="col">
            <div class="label">Diterima oleh</div>
            <div class="name">&nbsp;</div>
            <div class="role">Outlet</div>
        </div>
    </div>
</body>
</html>
