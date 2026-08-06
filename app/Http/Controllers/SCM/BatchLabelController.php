<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SCM\BatchLabel;
use App\Models\SCM\ProductionBatch;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cetak Label Batch — Gudang generate QR per produk (ProductionBatchItem)
 * dalam sebuah batch yang sudah approved, lalu cetak lewat halaman print
 * (mirip pola AssetQrController::bulk, browser print-to-PDF, bukan Dompdf).
 */
class BatchLabelController extends Controller
{
    /**
     * Generate label (kalau belum ada) utk semua item dalam batch, lalu
     * redirect ke halaman cetak.
     */
    public function store(Request $request, ProductionBatch $productionBatch): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::ADMIN), 403);
        abort_unless($productionBatch->status === ProductionBatch::STATUS_APPROVED, 422, 'Batch harus disetujui dulu sebelum label bisa dicetak.');

        // Nullable — tidak semua produk butuh ED (mis. barang non-consumable),
        // tapi kalau diisi dipakai sama utk semua produk dalam batch ini
        // (satu batch = satu run produksi = umumnya satu tanggal ED).
        $validated = $request->validate([
            'expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $productionBatch->load('items');

        foreach ($productionBatch->items as $item) {
            if ($item->labels()->exists()) {
                continue;
            }

            $labelCode = BatchLabel::generateLabelCode($item);

            $item->labels()->create([
                'label_code' => $labelCode,
                'qr_code' => $this->qrSvgFor($labelCode),
                'expiry_date' => $validated['expiry_date'] ?? null,
                'printed_at' => now(),
                'printed_by' => $user->id,
            ]);
        }

        return redirect()
            ->route('scm.batches.labels.print', $productionBatch)
            ->with('success', 'Label berhasil di-generate.');
    }

    public function print(Request $request, ProductionBatch $productionBatch): View
    {
        abort_unless($request->user()->hasRole(Role::GUDANG, Role::ADMIN), 403);

        $productionBatch->load('items.labels');

        return view('scm.batches.labels-print', [
            'productionBatch' => $productionBatch,
        ]);
    }

    private function qrSvgFor(string $labelCode): string
    {
        $result = (new Builder(writer: new SvgWriter()))->build(
            data: $labelCode,
            size: 200,
            margin: 6,
        );

        return $result->getString();
    }
}
