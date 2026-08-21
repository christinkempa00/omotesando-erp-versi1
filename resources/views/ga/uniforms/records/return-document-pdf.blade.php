@php
    // Base64-embed — pola sama persis dgn document-pdf.blade.php di folder
    // ini & document-pdf.blade.php milik GA Request.
    $returnSignatureSrc = null;
    if ($record->return_signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->return_signature_path)) {
        $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($record->return_signature_path);
        $returnSignatureSrc = 'data:image/png;base64,'.base64_encode($binary);
    }

    $receivedBySignatureSrc = null;
    if ($record->received_by_signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->received_by_signature_path)) {
        $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($record->received_by_signature_path);
        $receivedBySignatureSrc = 'data:image/png;base64,'.base64_encode($binary);
    }

    $checks = [
        ['label' => 'Jumlah barang sesuai', 'value' => $record->qty_sesuai, 'notes' => $record->qty_sesuai_notes],
        ['label' => 'Spesifikasi sesuai', 'value' => $record->spesifikasi_sesuai, 'notes' => $record->spesifikasi_sesuai_notes],
        ['label' => 'Kondisi sesuai', 'value' => $record->kondisi_sesuai, 'notes' => $record->kondisi_sesuai_notes],
    ];
@endphp
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

        table.info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.info td { padding: 3px 0; vertical-align: top; font-size: 11px; }
        table.info td.label { width: 22%; }
        table.info td.colon { width: 2%; }

        table.main { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.main th, table.main td { border: 1px solid #9ca3af; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.main th { background: #f3f4f6; font-size: 10px; }

        .checkbox { display: inline-block; width: 9px; height: 9px; border: 1px solid #374151; margin-right: 3px; text-align: center; line-height: 9px; font-size: 8px; }
        .checkbox.checked { background: #111827; color: #fff; }

        .catatan { margin-bottom: 16px; }
        .catatan h3 { font-size: 12px; margin: 0 0 4px 0; }
        .catatan p { margin: 0; min-height: 14px; }

        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signatures .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .signatures .label { font-size: 10px; color: #4b5563; margin-bottom: 34px; }
        .signatures .signature-img { display: block; max-height: 60px; margin-bottom: 12px; }
        .signatures .name { font-size: 11px; font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 3px; margin-top: 4px; }
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
        <h1>Pemeriksaan Pengembalian Barang</h1>
        <div class="no">No : {{ $record->record_code }}</div>
        <div class="outlet">{{ $record->branch?->name }}</div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Tanggal Pengembalian</td>
            <td class="colon">:</td>
            <td>{{ optional($record->return_date)->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penyerah</td>
            <td class="colon">:</td>
            <td>{{ $record->returned_by_name ?: $record->employee_name }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penerima</td>
            <td class="colon">:</td>
            <td>{{ $record->received_by_name ?: '-' }}</td>
        </tr>
    </table>

    <table class="main">
        <thead>
            <tr>
                <th>Item Pemeriksaan</th>
                <th style="width: 20%;">Status</th>
                <th style="width: 34%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($checks as $check)
                <tr>
                    <td>{{ $check['label'] }}</td>
                    <td>
                        <span class="checkbox {{ $check['value'] === true ? 'checked' : '' }}">{{ $check['value'] === true ? 'X' : '' }}</span> Ya
                        &nbsp;&nbsp;
                        <span class="checkbox {{ $check['value'] === false ? 'checked' : '' }}">{{ $check['value'] === false ? 'X' : '' }}</span> Tidak
                    </td>
                    <td>{{ $check['notes'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="catatan">
        <h3>Catatan:</h3>
        <p>{{ $record->return_notes ?: '-' }}</p>
    </div>

    <div class="signatures">
        <div class="col">
            <div class="label" @if ($returnSignatureSrc) style="margin-bottom: 4px;" @endif>Diserahkan Oleh</div>
            @if ($returnSignatureSrc)
                <img src="{{ $returnSignatureSrc }}" class="signature-img">
            @endif
            <div class="name">{{ $record->returned_by_name ?: $record->employee_name }}</div>
            <div class="role">Karyawan</div>
        </div>
        <div class="col">
            <div class="label" @if ($receivedBySignatureSrc) style="margin-bottom: 4px;" @endif>Diterima Oleh</div>
            @if ($receivedBySignatureSrc)
                <img src="{{ $receivedBySignatureSrc }}" class="signature-img">
            @endif
            <div class="name">{{ $record->received_by_name ?: '-' }}</div>
            <div class="role">General Affairs</div>
        </div>
    </div>
</body>
</html>
