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
        .title .outlet { font-size: 11px; font-weight: bold; margin-top: 2px; }
        .title .date { text-align: right; font-size: 10px; margin-top: 4px; }

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
        <h1>Bukti Pengambilan Bahan</h1>
        <div class="no">No : {{ $materialRequest->request_number }}</div>
        <div class="outlet">{{ $materialRequest->branch?->name }}</div>
        <div class="date">{{ $materialRequest->created_at->translatedFormat('l, d F Y') }}</div>
    </div>

    @if ($materialRequest->description)
        <table class="main">
            <thead><tr><th>Keterangan</th></tr></thead>
            <tbody><tr><td>{{ $materialRequest->description }}</td></tr></tbody>
        </table>
    @endif

    <table class="main">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th>Bahan</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 15%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($materialRequest->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td class="text-right">{{ $item->qty }}</td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php $step1 = $materialRequest->approvals->firstWhere('step', 1); @endphp

    <div class="signatures">
        <div class="col">
            <div class="label">Diajukan oleh</div>
            <div class="name">{{ $materialRequest->requestedBy?->name ?: '-' }}</div>
            <div class="role">Produksi</div>
        </div>
        <div class="col">
            <div class="label">Disetujui oleh</div>
            <div class="name">{{ $step1?->approver?->name ?: '-' }}</div>
            <div class="role">Admin</div>
        </div>
        <div class="col">
            <div class="label">Diserahkan oleh</div>
            <div class="name">&nbsp;</div>
            <div class="role">Gudang</div>
        </div>
    </div>
</body>
</html>
