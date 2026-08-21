@once
    <script>
        window.QR_LABEL_SIZES = [
            { key: '50x70', label: '50 × 70 mm', w: 50, h: 70 },
            { key: '40x30', label: '40 × 30 mm', w: 40, h: 30 },
            { key: '50x30', label: '50 × 30 mm', w: 50, h: 30 },
            { key: '30x170', label: '30 × 170 mm', w: 30, h: 170 },
            { key: '10x20', label: '10 × 20 mm', w: 10, h: 20 },
        ];

        const QR_LABEL_PX_PER_MM = 8; // ~203 DPI, umum utk printer label thermal

        // Menyusutkan ukuran font sampai muat di maxWidth (bukan memotong teks
        // duluan) — supaya label kecil tetap menampilkan info penuh selama
        // masih mungkin dibaca, dan cuma dipotong "…" kalau sudah di font
        // terkecil dan tetap tidak muat.
        function qrLabelFitText(ctx, text, x, y, maxWidth, maxFontSize, minFontSize, weight, align) {
            if (!text) return y;
            ctx.textAlign = align;
            let t = String(text).toUpperCase();
            let fontSize = maxFontSize;
            while (fontSize > minFontSize) {
                ctx.font = `${weight} ${fontSize}px sans-serif`;
                if (ctx.measureText(t).width <= maxWidth) break;
                fontSize -= 1;
            }
            ctx.font = `${weight} ${fontSize}px sans-serif`;
            while (ctx.measureText(t).width > maxWidth && t.length > 3) {
                t = t.slice(0, -2);
            }
            if (t.length < String(text).length) t = t.slice(0, -1) + '…';
            ctx.fillText(t, x, y);
            return y + fontSize + Math.round(fontSize * 0.35);
        }

        // Layout selalu sejajar (QR kiri, teks kanan) — sama persis dengan
        // preview di layar (qr-label.blade.php / qr-labels.blade.php), dipakai
        // di semua ukuran, cuma dimensi kanvasnya yang menyesuaikan pilihan user.
        function drawSideBySideLabel(ctx, img, asset, pxW, pxH) {
            const margin = Math.round(Math.min(pxW, pxH) * 0.07);

            // Label super sempit (mis. 10x20mm) nggak cukup lebar utk QR + 4 baris
            // teks deskriptif bersebelahan — QR dapat porsi lebih besar, teks
            // diringkas jadi kode aset saja supaya tetap kebaca (bukan terpotong
            // jadi "HEAD…", "LAP…", dst).
            const tight = pxW < 15 * QR_LABEL_PX_PER_MM;
            let qrSide;
            if (tight) {
                // Jamin kolom teks minimal cukup lebar utk kode aset (bukan cuma
                // 1-2 karakter lalu "…") — QR menyesuaikan sisa ruangnya, bukan
                // sebaliknya.
                const minCodeTextW = 32;
                qrSide = Math.min(pxH - margin * 2, pxW - margin * 3 - minCodeTextW);
                qrSide = Math.max(20, qrSide);
            } else {
                qrSide = Math.min(pxH - margin * 2, pxW * 0.42);
            }
            const qrX = margin;
            // QR dipusatkan vertikal seperti biasa, KECUALI kanvasnya jauh lebih
            // tinggi drpd sisi QR-nya (mis. label 30x170mm) — pada kasus itu QR +
            // teks disematkan ke atas supaya tidak "mengambang" renggang di
            // tengah kanvas yang jauh lebih panjang.
            const qrY = pxH > qrSide * 5 ? margin : (pxH - qrSide) / 2;
            ctx.drawImage(img, qrX, qrY, qrSide, qrSide);

            const textX = margin * 2 + qrSide;
            const textW = pxW - textX - margin;
            // Baris teks disebar mengikuti TINGGI QR (bukan tinggi kanvas penuh)
            // supaya tetap sejajar rapat dengan QR-nya, bukannya renggang di
            // label yang jauh lebih panjang drpd sisi QR-nya.
            const rowsTop = qrY;
            const rowsH = qrSide;

            if (tight) {
                const codeFont = Math.min(Math.max(Math.round(rowsH * 0.3), 8), 28);
                qrLabelFitText(ctx, asset.serial || asset.code, textX, rowsTop + rowsH / 2 - codeFont / 2, textW, codeFont, 8, 'bold', 'left');
                return;
            }

            const branchFont = Math.min(Math.max(Math.round(qrSide * 0.14), 10), 34);
            const nameFont = Math.min(Math.max(Math.round(qrSide * 0.16), 10), 40);
            const locationFont = Math.min(Math.max(Math.round(qrSide * 0.11), 8), 28);
            const labelFont = Math.min(Math.max(Math.round(qrSide * 0.07), 8), 16);
            const serialFont = Math.min(Math.max(Math.round(qrSide * 0.17), 10), 42);

            qrLabelFitText(ctx, asset.branch, textX, rowsTop + rowsH * 0.04, textW, branchFont, 8, 'bold', 'left');
            qrLabelFitText(ctx, asset.name, textX, rowsTop + rowsH * 0.27, textW, nameFont, 8, 'bold', 'left');
            if (asset.location) {
                qrLabelFitText(ctx, asset.location, textX, rowsTop + rowsH * 0.52, textW, locationFont, 8, 'normal', 'left');
            }

            const dividerY = rowsTop + rowsH * 0.67;
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = Math.max(1, Math.round(qrSide * 0.008));
            ctx.beginPath();
            ctx.moveTo(textX, dividerY);
            ctx.lineTo(textX + textW, dividerY);
            ctx.stroke();

            qrLabelFitText(ctx, 'NOMOR SERIAL', textX, rowsTop + rowsH * 0.73, textW, labelFont, 8, 'bold', 'left');
            qrLabelFitText(ctx, asset.serial || asset.code, textX, rowsTop + rowsH * 0.81, textW, serialFont, 8, 'bold', 'left');
        }

        function buildQrLabelCanvas(asset, sizeKey) {
            return new Promise((resolve, reject) => {
                const size = window.QR_LABEL_SIZES.find(s => s.key === sizeKey) || window.QR_LABEL_SIZES[0];
                const pxW = Math.round(size.w * QR_LABEL_PX_PER_MM);
                const pxH = Math.round(size.h * QR_LABEL_PX_PER_MM);
                const canvas = document.createElement('canvas');
                canvas.width = pxW;
                canvas.height = pxH;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, pxW, pxH);
                ctx.fillStyle = '#111827';
                ctx.textBaseline = 'top';

                const img = new Image();
                img.onload = () => {
                    drawSideBySideLabel(ctx, img, asset, pxW, pxH);
                    resolve(canvas);
                };
                img.onerror = reject;
                img.src = asset.qr;
            });
        }

        // PNG dari canvas.toBlob() TIDAK menyimpan info DPI/ukuran fisik sama
        // sekali — kalau di-print langsung (mis. Ctrl+P dari browser), OS/dialog
        // print biasanya asumsi 96 DPI generik, bukan 8px/mm (~203 DPI) yang
        // dipakai saat menggambar kanvasnya. Akibatnya hasil cetak fisik jadi
        // lebih kecil dari ukuran label yang dipilih, nyisa banyak area kosong.
        // Ini nyisipin chunk "pHYs" standar PNG (pixel-per-meter) supaya
        // viewer/print dialog yang membaca metadata ini tau ukuran fisik
        // sebenarnya, dan tercetak pas 100%/actual size.
        function qrPngWithDpi(arrayBuffer, pxPerMm) {
            const pxPerMeter = Math.round(pxPerMm * 1000);
            const bytes = new Uint8Array(arrayBuffer);

            // IHDR selalu chunk pertama tepat setelah 8-byte signature PNG, dan
            // selalu panjang tetap (4 len + 4 type + 13 data + 4 crc = 25 byte).
            const ihdrEnd = 8 + 25;

            const physData = new Uint8Array(9);
            const dv = new DataView(physData.buffer);
            dv.setUint32(0, pxPerMeter); // pixels per unit, X
            dv.setUint32(4, pxPerMeter); // pixels per unit, Y
            physData[8] = 1; // unit specifier: 1 = meter

            const type = new Uint8Array([0x70, 0x48, 0x59, 0x73]); // "pHYs"
            const crcInput = new Uint8Array(type.length + physData.length);
            crcInput.set(type, 0);
            crcInput.set(physData, type.length);
            const crc = qrCrc32(crcInput);

            const chunk = new Uint8Array(4 + 4 + 9 + 4);
            const chunkView = new DataView(chunk.buffer);
            chunkView.setUint32(0, 9); // length of data
            chunk.set(type, 4);
            chunk.set(physData, 8);
            chunkView.setUint32(17, crc >>> 0);

            const out = new Uint8Array(bytes.length + chunk.length);
            out.set(bytes.subarray(0, ihdrEnd), 0);
            out.set(chunk, ihdrEnd);
            out.set(bytes.subarray(ihdrEnd), ihdrEnd + chunk.length);
            return out;
        }

        let qrCrcTable = null;
        function qrCrc32(bytes) {
            if (!qrCrcTable) {
                qrCrcTable = new Uint32Array(256);
                for (let n = 0; n < 256; n++) {
                    let c = n;
                    for (let k = 0; k < 8; k++) {
                        c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
                    }
                    qrCrcTable[n] = c >>> 0;
                }
            }
            let crc = 0xFFFFFFFF;
            for (let i = 0; i < bytes.length; i++) {
                crc = qrCrcTable[(crc ^ bytes[i]) & 0xFF] ^ (crc >>> 8);
            }
            return (crc ^ 0xFFFFFFFF) >>> 0;
        }

        function downloadCanvasAsPng(canvas, filename) {
            canvas.toBlob(async (blob) => {
                const buffer = await blob.arrayBuffer();
                const withDpi = qrPngWithDpi(buffer, QR_LABEL_PX_PER_MM);
                const url = URL.createObjectURL(new Blob([withDpi], { type: 'image/png' }));
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 2000);
            }, 'image/png');
        }

        function populateQrSizeSelect(select) {
            window.QR_LABEL_SIZES.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.key;
                opt.textContent = s.label;
                select.appendChild(opt);
            });
        }
    </script>
@endonce
