<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SCM\ScmReportController;
use App\Models\Branch;
use App\Models\SCM\DiscrepancyReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring SCM utk Head — read-only, sama pola dengan HeadAssetController/
 * HeadUniformController/HeadMaintenanceController (halaman terpisah dari
 * modul operasionalnya, reuse query yang sama supaya angkanya konsisten).
 * Reuse ScmReportController::filteredQuery()/summaryFor() spy rekap yang
 * Head lihat selalu sama dengan yang Admin lihat.
 */
class HeadScmController extends Controller
{
    public function index(Request $request): View
    {
        $reportController = new ScmReportController;

        $deliveryNotes = $reportController->filteredQuery($request)->paginate(10, ['*'], 'deliveries_page')->withQueryString();
        $summary = $reportController->summaryFor($reportController->filteredQuery($request)->get());

        $discrepancyQuery = DiscrepancyReport::with(['deliveryNote.fromBranch', 'deliveryNote.toBranch', 'deliveryNoteItem.batchLabel.productionBatchItem']);

        if ($branchId = $request->query('branch_id')) {
            $discrepancyQuery->whereHas('deliveryNote', fn ($q) => $q->where('to_branch_id', $branchId));
        }
        if ($dateFrom = $request->query('date_from')) {
            $discrepancyQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $discrepancyQuery->whereDate('created_at', '<=', $dateTo);
        }

        $discrepancies = $discrepancyQuery->latest()->paginate(10, ['*'], 'discrepancies_page')->withQueryString();

        return view('head.scm.index', [
            'deliveryNotes' => $deliveryNotes,
            'summary' => $summary,
            'discrepancies' => $discrepancies,
            'branches' => Branch::orderedOutlets(),
            'selectedBranchId' => $branchId ?? null,
            'dateFrom' => $dateFrom ?? null,
            'dateTo' => $dateTo ?? null,
        ]);
    }
}
