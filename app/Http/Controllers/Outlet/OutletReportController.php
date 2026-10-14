<?php

namespace App\Http\Controllers\Outlet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Outlet\StoreOutletReportRequest;
use App\Models\GA\OutletInspectionArea;
use App\Models\GA\OutletReport;
use App\Models\GA\OutletReportPhoto;
use App\Services\GA\PhotoWatermarkService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sisi Outlet: kirim & lihat laporan dokumentasi kebersihan sendiri
 * (opening/closing). Read-only sisi GA ada di controller terpisah
 * (App\Http\Controllers\GA\OutletReportController, Batch 4) — BUKAN
 * approval, cuma pemantauan.
 */
class OutletReportController extends Controller
{
    public function __construct(private PhotoWatermarkService $watermarkService)
    {
    }

    public function index(Request $request): View
    {
        $branch = $request->user()->scopingBranch();

        $reports = OutletReport::where('branch_id', $branch->id)
            ->with('photos')
            ->latest('report_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('outlet.reports.index', ['reports' => $reports]);
    }

    public function create(Request $request): View
    {
        $branch = $request->user()->scopingBranch();

        $areas = OutletInspectionArea::where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('outlet.reports.create', [
            'areas' => $areas,
            'sessions' => OutletReport::sessionLabels(),
        ]);
    }

    public function store(StoreOutletReportRequest $request): RedirectResponse
    {
        $branch = $request->user()->scopingBranch();
        $validated = $request->validated();

        // Guard duplikat ramah SEBELUM insert (selain unique constraint DB,
        // supaya user langsung dapat pesan jelas, bukan 500).
        $duplicate = OutletReport::where('branch_id', $branch->id)
            ->where('report_date', $validated['report_date'])
            ->where('session', $validated['session'])
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'session' => 'Laporan sesi ini untuk tanggal tersebut sudah ada.',
            ]);
        }

        try {
            $report = DB::transaction(function () use ($request, $branch, $validated) {
                $report = OutletReport::create([
                    'branch_id' => $branch->id,
                    'report_date' => $validated['report_date'],
                    'session' => $validated['session'],
                    'notes' => $validated['notes'] ?? null,
                    'submitted_by' => $request->user()->id,
                ]);

                $photosByArea = $request->file('photos') ?? [];
                $latByArea = (array) $request->input('latitude', []);
                $lngByArea = (array) $request->input('longitude', []);

                foreach ($photosByArea as $areaId => $files) {
                    foreach ($files as $index => $file) {
                        $latitude = (float) ($latByArea[$areaId][$index] ?? 0);
                        $longitude = (float) ($lngByArea[$areaId][$index] ?? 0);
                        $takenAt = now();

                        $path = $this->watermarkService->stampAndStore(
                            $file,
                            "outlet-reports/{$branch->id}/{$report->id}",
                            $branch->name,
                            $latitude,
                            $longitude,
                            $takenAt
                        );

                        OutletReportPhoto::create([
                            'outlet_report_id' => $report->id,
                            'inspection_area_id' => $areaId,
                            'photo_path' => $path,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'taken_at' => $takenAt,
                        ]);
                    }
                }

                return $report;
            });
        } catch (QueryException $e) {
            // Jaring pengaman race condition pada unique constraint (dua
            // submit bersamaan lolos guard duplikat di atas). Error lain
            // (bukan pelanggaran constraint) tetap dilempar apa adanya.
            if ($e->getCode() === '23000') {
                return back()->withInput()->withErrors([
                    'session' => 'Laporan sesi ini untuk tanggal tersebut sudah ada.',
                ]);
            }

            throw $e;
        }

        return redirect()->route('outlet.reports.show', $report)
            ->with('success', 'Laporan kebersihan berhasil dikirim.');
    }

    public function show(Request $request, OutletReport $report): View
    {
        abort_unless($report->branch_id === $request->user()->scopingBranch()->id, 404);

        $report->load('photos.inspectionArea');

        return view('outlet.reports.show', ['report' => $report]);
    }
}
