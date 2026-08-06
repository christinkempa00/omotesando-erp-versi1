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
        table.main th { background: #f3f4f6; font-size: 10px; width: 30%; }

        .signatures { display: table; width: 100%; margin-top: 40px; }
        .signatures .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; text-align: center; }
        .signatures .label { font-size: 10px; color: #4b5563; margin-bottom: 6px; }
        .signatures .sig-box { height: 60px; display: flex; align-items: flex-end; justify-content: center; }
        .signatures .sig-box img { max-height: 55px; max-width: 160px; }
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
        <h1>Berita Acara Serah Terima Seragam</h1>
        <div class="no">No : {{ $record->record_code }}</div>
        <div class="outlet">{{ $record->branch?->name }}</div>
        <div class="date">{{ $record->issue_date->translatedFormat('l, d F Y') }}</div>
    </div>

    <table class="main">
        <tbody>
            <tr>
                <th>Nama Karyawan</th>
                <td>{{ $record->employee_name }}</td>
            </tr>
            <tr>
                <th>Outlet</th>
                <td>{{ $record->branch?->name }}</td>
            </tr>
            <tr>
                <th>Jenis Seragam</th>
                <td>
                    {{ $record->uniform_type }}
                    @if ($record->size) &middot; Ukuran {{ $record->size }} @endif
                    @if ($record->color) &middot; {{ $record->color }} @endif
                </td>
            </tr>
            <tr>
                <th>Tanggal Serah</th>
                <td>{{ $record->issue_date->translatedFormat('d F Y') }}</td>
            </tr>
            @if ($record->status === \App\Models\GA\UniformRecord::STATUS_RETURNED)
                <tr>
                    <th>Tanggal Kembali</th>
                    <td>
                        {{ optional($record->return_date)->translatedFormat('d F Y') }}
                        &middot; Kondisi: {{ $conditionLabels[$record->return_condition] ?? $record->return_condition }}
                    </td>
                </tr>
            @endif
            @if ($record->issue_notes)
                <tr>
                    <th>Catatan</th>
                    <td>{{ $record->issue_notes }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <p>
        Dengan ini menyatakan bahwa seragam kerja dengan rincian di atas telah diserahkan oleh pihak
        General Affairs kepada karyawan yang bersangkutan dalam kondisi baik, dan diterima sesuai
        dengan ketentuan yang berlaku.
    </p>

    <div class="signatures">
        <div class="col">
            <div class="label">Diserahkan oleh (General Affairs)</div>
            <div class="sig-box"></div>
            <div class="name">{{ $record->createdBy?->name ?: '-' }}</div>
            <div class="role">General Affairs</div>
        </div>
        <div class="col">
            <div class="label">Diterima oleh (Karyawan)</div>
            <div class="sig-box">
                @if ($record->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($record->signature_path))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($record->signature_path) }}">
                @endif
            </div>
            <div class="name">{{ $record->employee_name }}</div>
            <div class="role">Karyawan</div>
        </div>
    </div>
</body>
</html>
