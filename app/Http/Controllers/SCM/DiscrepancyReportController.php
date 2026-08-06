<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SCM\DiscrepancyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan Selisih — SEMUA baris di sini dibuat otomatis oleh
 * DeliveryNoteItemObserver, halaman ini read-only (tidak ada create/edit
 * manual). Akses Admin (lihat Module::SCM_REPORTS); Head punya halaman
 * monitoring terpisah (lihat HeadScmController).
 */
class DiscrepancyReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $query = DiscrepancyReport::with([
            'deliveryNote.fromBranch',
            'deliveryNote.toBranch',
            'deliveryNoteItem.batchLabel.productionBatchItem',
        ]);

        if ($branchId = $request->query('branch_id')) {
            $query->whereHas('deliveryNote', fn ($q) => $q->where('to_branch_id', $branchId));
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $reports = $query->latest()->paginate(15)->withQueryString();

        return view('scm.discrepancies.index', [
            'reports' => $reports,
            'branches' => Branch::orderedOutlets(),
            'selectedBranchId' => $branchId ?? null,
            'dateFrom' => $dateFrom ?? null,
            'dateTo' => $dateTo ?? null,
        ]);
    }

    public function document(Request $request, DiscrepancyReport $discrepancyReport): Response
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $discrepancyReport->load(['deliveryNote.fromBranch', 'deliveryNote.toBranch', 'deliveryNoteItem.batchLabel.productionBatchItem']);

        $pdf = Pdf::loadView('scm.discrepancies.document-pdf', [
            'report' => $discrepancyReport,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-selisih-'.$discrepancyReport->id.'.pdf');
    }
}
