<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Models\GA\Asset;
use App\Models\GA\MaintenanceJob;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetQrController extends Controller
{
    /**
     * Label QR cetak untuk satu aset.
     */
    public function show(Asset $asset): View
    {
        $asset->load('branch');

        return view('ga.assets.qr-label', [
            'asset' => $asset,
            'qrSvg' => $this->qrSvgFor($asset),
            'qrPngDataUri' => $this->qrPngDataUriFor($asset),
        ]);
    }

    /**
     * Halaman publik read-only yang dituju oleh QR fisik di aset — tanpa login,
     * hanya menampilkan info identifikasi (bukan data finansial/vendor).
     */
    public function publicShow(Asset $asset): View
    {
        $asset->load('branch');

        $maintenanceJobs = $asset->maintenanceJobs()
            ->orderByDesc('scheduled_date')
            ->orderByDesc('id')
            ->get();

        $ongoingMaintenance = $maintenanceJobs
            ->whereIn('status', [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS]);

        $maintenanceHistory = $maintenanceJobs
            ->whereIn('status', [MaintenanceJob::STATUS_COMPLETED, MaintenanceJob::STATUS_CANCELLED])
            ->take(5);

        return view('ga.assets.public', [
            'asset' => $asset,
            'statusLabels' => Asset::statusLabels(),
            'ongoingMaintenance' => $ongoingMaintenance,
            'maintenanceHistory' => $maintenanceHistory,
            'maintenanceStatusLabels' => MaintenanceJob::statusLabels(),
            'maintenanceTypeLabels' => MaintenanceJob::typeLabels(),
        ]);
    }

    /**
     * Lembar cetak QR untuk banyak aset sekaligus: baik yang dipilih via checkbox
     * (ids[]), maupun mengikuti filter yang sama dengan daftar aset kalau tidak
     * ada yang dipilih secara eksplisit.
     */
    public function bulk(Request $request): View
    {
        if ($request->filled('ids')) {
            $assets = Asset::with('branch')
                ->whereIn('id', $request->input('ids'))
                ->orderBy('asset_code')
                ->get();
        } else {
            $query = Asset::with('branch');

            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            if ($search = $request->query('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                });
            }

            if ($branchId = $request->query('branch_id')) {
                $query->where('branch_id', $branchId);
            }

            $assets = $query->orderBy('asset_code')->get();
        }

        return view('ga.assets.qr-labels', [
            'assets' => $assets,
            'qrSvgs' => $assets->mapWithKeys(fn (Asset $asset) => [$asset->id => $this->qrSvgFor($asset)]),
            'qrPngDataUris' => $assets->mapWithKeys(fn (Asset $asset) => [$asset->id => $this->qrPngDataUriFor($asset)]),
        ]);
    }

    private function qrSvgFor(Asset $asset): string
    {
        $result = (new Builder(writer: new SvgWriter()))->build(
            data: route('assets.public', $asset),
            size: 200,
            margin: 6,
        );

        return $result->getString();
    }

    /**
     * QR sebagai PNG data URI — dipakai JS di sisi klien (Canvas) untuk
     * menyusun gambar label sesuai ukuran fisik yang dipilih user, lalu
     * diunduh sebagai file .png (bukan cetak/PDF).
     */
    private function qrPngDataUriFor(Asset $asset): string
    {
        $result = (new Builder(writer: new PngWriter()))->build(
            data: route('assets.public', $asset),
            size: 600,
            margin: 12,
        );

        return $result->getDataUri();
    }
}
