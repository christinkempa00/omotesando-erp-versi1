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

        .checkbox { display: inline-block; width: 9px; height: 9px; border: 1px solid #374151; margin-right: 5px; text-align: center; line-height: 9px; font-size: 8px; }
        .checkbox.checked { background: #111827; color: #fff; }
        .tujuan-row { margin-bottom: 4px; }
        .tujuan-row.selected { font-weight: bold; }

        h3.section { font-size: 12px; margin: 0 0 6px 0; }

        .text-right { text-align: right; }

        .signatures { display: table; width: 100%; margin-top: 30px; }
        .signatures .col { display: table-cell; width: 30%; vertical-align: top; padding-right: 10px; }
        .signatures .label { font-size: 10px; color: #4b5563; margin-bottom: 34px; }
        .signatures .name { font-size: 11px; font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 3px; }
        .signatures .role { font-size: 10px; color: #4b5563; }
        .signatures .signature-img { display: block; max-height: 60px; margin-bottom: 12px; }
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
        <h1>General Affair Request (GAR)</h1>
        <div class="no">No : {{ $gaRequest->request_number }}</div>
        <div class="outlet">{{ $gaRequest->outletLabel() }}</div>
        <div class="date">{{ $gaRequest->created_at->translatedFormat('l, d F Y') }}</div>
    </div>

    <table class="main">
        <thead>
            <tr>
                <th style="width: 45%;">Tujuan</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @foreach ($categoryLabels as $value => $label)
                        <div class="tujuan-row {{ $gaRequest->category === $value ? 'selected' : '' }}">
                            <span class="checkbox {{ $gaRequest->category === $value ? 'checked' : '' }}">{{ $gaRequest->category === $value ? 'X' : '' }}</span>
                            {{ $label }}
                        </div>
                    @endforeach
                </td>
                <td>{{ $gaRequest->description }}</td>
            </tr>
        </tbody>
    </table>

    <h3 class="section">Detail Pembelian/Pengerjaan</h3>
    <table class="main">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th>Item</th>
                <th style="width: 12%;">Tipe</th>
                <th style="width: 7%;">Qty</th>
                <th style="width: 7%;">Satuan</th>
                <th style="width: 13%;">Price/pcs</th>
                <th style="width: 13%;">Total</th>
                <th style="width: 14%;">Vendor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($gaRequest->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->item_name }}</td>
                    <td>{{ $item->type ?: '-' }}</td>
                    <td class="text-right">{{ $item->qty }}</td>
                    <td>{{ $item->unit ?: '-' }}</td>
                    <td class="text-right">Rp {{ number_format((float) $item->price_per_unit, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((float) $item->total, 0, ',', '.') }}</td>
                    <td>{{ $item->vendor_name ?: '-' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="text-right">Sub Total</td>
                <td class="text-right">Rp {{ number_format((float) $gaRequest->subtotal_amount, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            @if ($gaRequest->discount_percent)
                <tr>
                    <td colspan="6" class="text-right">Diskon ({{ \App\Models\GA\GaRequest::formatPercent($gaRequest->discount_percent) }}%)</td>
                    <td class="text-right">- Rp {{ number_format($gaRequest->discountAmount(), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endif
            @if ($gaRequest->pph_percent)
                <tr>
                    <td colspan="6" class="text-right">PPH ({{ \App\Models\GA\GaRequest::formatPercent($gaRequest->pph_percent) }}%)</td>
                    <td class="text-right">- Rp {{ number_format($gaRequest->pphAmount(), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @endif
            <tr>
                <td colspan="6" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>Rp {{ number_format((float) $gaRequest->total_amount, 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @php
        // Tanda tangan gambar (bukan e-signature tersertifikasi) — ditempel
        // sebagai <img> base64 di atas nama pemohon kalau signature_path-nya
        // ada. Base64-embed (bukan URL) supaya tampil di Dompdf terlepas
        // dari konfigurasi remote-resource-nya.
        $signaturePathToImage = function (?string $path): ?string {
            if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return null;
            }

            $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($path);

            return 'data:image/png;base64,'.base64_encode($binary);
        };
        $requesterSignatureSrc = $signaturePathToImage($gaRequest->requester_signature_path);
    @endphp

    <div class="signatures">
        <div class="col">
            <div class="label" @if ($requesterSignatureSrc) style="margin-bottom: 4px;" @endif>Pemohon</div>
            @if ($requesterSignatureSrc)
                <img src="{{ $requesterSignatureSrc }}" class="signature-img">
            @endif
            <div class="name">{{ $gaRequest->requester_name ?: ($gaRequest->requestedBy?->name ?: '-') }}</div>
            <div class="role">{{ $gaRequest->division?->name ?: '-' }}</div>
        </div>
    </div>
</body>
</html>
