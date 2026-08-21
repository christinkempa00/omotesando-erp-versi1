@php
    // Base64-embed (bukan path/URL) supaya tampil konsisten baik lokal
    // maupun di hosting split-public (Storage::disk('public')->path() bisa
    // lempar "Cannot resolve public path" di setup seperti itu) — pola sama
    // persis dgn signature GA Request di document-pdf.blade.php GA Request.
    $signatureSrc = null;
    if ($record->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->signature_path)) {
        $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($record->signature_path);
        $signatureSrc = 'data:image/png;base64,'.base64_encode($binary);
    }

    $issuedBySignatureSrc = null;
    if ($record->issued_by_signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->issued_by_signature_path)) {
        $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($record->issued_by_signature_path);
        $issuedBySignatureSrc = 'data:image/png;base64,'.base64_encode($binary);
    }

    // Record lama (sebelum fitur multi-item) tidak punya baris di items —
    // sintesis satu baris dari field lama supaya tabel tetap tampil normal.
    $rows = $record->items->isNotEmpty()
        ? $record->items
        : collect([(object) [
            'uniform_type' => $record->uniform_type,
            'size' => $record->size,
            'color' => $record->color,
            'qty' => 1,
            'item_condition' => null,
            'item_notes' => $record->issue_notes,
        ]]);

    // Template cetak (mengikuti format Berita Acara Serah Terima manual)
    // selalu punya minimal 5 baris bernomor, baris kosong tetap ditampilkan
    // kalau itemnya kurang dari 5 — kalau lebih dari 5 item, semuanya tetap
    // ditampilkan (tidak dipotong).
    $minRows = 5;
    if ($rows->count() < $minRows) {
        $rows = $rows->concat(array_fill(0, $minRows - $rows->count(), null));
    }
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
        .text-right { text-align: right; }

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
        <h1>Serah Terima Barang</h1>
        <div class="no">No : {{ $record->record_code }}</div>
        <div class="outlet">{{ $record->branch?->name }}</div>
    </div>

    <table class="info">
        <tr>
            <td class="label">Tanggal Penyerahan</td>
            <td class="colon">:</td>
            <td>{{ $record->issue_date->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penyerah</td>
            <td class="colon">:</td>
            <td>{{ $record->issued_by_name ?: ($record->createdBy?->name ?: '-') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Penerima</td>
            <td class="colon">:</td>
            <td>{{ $record->employee_name }}</td>
        </tr>
    </table>

    <table class="main">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th>Nama Barang</th>
                <th style="width: 18%;">Spesifikasi</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 12%;">Kondisi</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    @if ($row)
                        @php
                            $spec = collect([$row->size, $row->color])->filter()->implode(' / ') ?: '-';
                        @endphp
                        <td>{{ $row->uniform_type }}</td>
                        <td>{{ $spec }}</td>
                        <td class="text-right">{{ $row->qty }}</td>
                        <td>{{ $row->item_condition ?: '-' }}</td>
                        <td>{{ $row->item_notes ?: '-' }}</td>
                    @else
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="col">
            <div class="label" @if ($issuedBySignatureSrc) style="margin-bottom: 4px;" @endif>Diserahkan Oleh</div>
            @if ($issuedBySignatureSrc)
                <img src="{{ $issuedBySignatureSrc }}" class="signature-img">
            @endif
            <div class="name">{{ $record->issued_by_name ?: ($record->createdBy?->name ?: '-') }}</div>
            <div class="role">General Affairs</div>
        </div>
        <div class="col">
            <div class="label" @if ($signatureSrc) style="margin-bottom: 4px;" @endif>Diterima Oleh</div>
            @if ($signatureSrc)
                <img src="{{ $signatureSrc }}" class="signature-img">
            @endif
            <div class="name">{{ $record->employee_name }}</div>
            <div class="role">Karyawan</div>
        </div>
    </div>
</body>
</html>
