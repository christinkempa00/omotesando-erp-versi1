<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Purchasing\GoodsReceiptItem;
use App\Models\Role;
use App\Models\SCM\BatchLabel;
use App\Models\SCM\DeliveryNote;
use App\Models\SCM\StockBalance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rekap Periodik — semua pengiriman & selisih per periode/outlet. Akses
 * Admin (lihat Module::SCM_REPORTS); Head punya halaman monitoring
 * terpisah (lihat HeadScmController) yang reuse filteredQuery() ini.
 */
class ScmReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $deliveryNotes = $this->filteredQuery($request)->paginate(15)->withQueryString();

        return view('scm.reports.index', [
            'deliveryNotes' => $deliveryNotes,
            'branches' => Branch::orderedOutlets(),
            'statusLabels' => DeliveryNote::statusLabels(),
            'selectedBranchId' => $request->query('branch_id'),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
            'summary' => $this->summaryFor($this->filteredQuery($request)->get()),
        ]);
    }

    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $deliveryNotes = $this->filteredQuery($request)->get();

        $pdf = Pdf::loadView('scm.reports.export-pdf', [
            'deliveryNotes' => $deliveryNotes,
            'summary' => $this->summaryFor($deliveryNotes),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-scm-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Batch/barang dengan ED mendekati (default 7 hari), dikelompokkan per
     * branch_id — hanya yang masih ada stoknya (qty_on_hand > 0) yang
     * relevan ditampilkan. Mencakup 2 sumber stok (lihat Fase 2): BatchLabel
     * (hasil produksi) & GoodsReceiptItem (bahan makanan dari supplier).
     * Diakses Admin & Gudang (operasional gudang harian), bukan cuma Admin
     * seperti reports/rekap.
     */
    public function nearExpiry(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::GUDANG), 403);

        $days = max(1, (int) $request->query('days', 7));

        return view('scm.reports.near-expiry', [
            'days' => $days,
            'groups' => $this->nearExpiryBalances($days)->groupBy('branch_id'),
        ]);
    }

    public function nearExpiryExportExcel(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::GUDANG), 403);

        $days = max(1, (int) $request->query('days', 7));
        $balances = $this->nearExpiryBalances($days);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok Mendekati ED');

        $header = ['Outlet', 'Label', 'Produk', 'Qty', 'Satuan', 'Kedaluwarsa', 'Sisa Hari'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($balances as $balance) {
            [$code, $itemName, $unit] = $this->nearExpiryRowData($balance);

            $sheet->fromArray([
                $balance->branch?->name,
                $code,
                $itemName,
                $balance->qty_on_hand,
                $unit,
                $balance->stockable->expiry_date->format('d/m/Y'),
                $balance->stockable->daysUntilExpiry(),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'stok-mendekati-ed-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function nearExpiryExportPdf(Request $request)
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::GUDANG), 403);

        $days = max(1, (int) $request->query('days', 7));
        $balances = $this->nearExpiryBalances($days);

        $pdf = Pdf::loadView('scm.reports.near-expiry-export-pdf', [
            'balances' => $balances,
            'days' => $days,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('stok-mendekati-ed-'.now()->format('Ymd-His').'.pdf');
    }

    private function nearExpiryBalances(int $days)
    {
        return StockBalance::with(['branch'])
            ->with(['stockable' => function ($morphTo) {
                $morphTo->morphWith([
                    BatchLabel::class => ['productionBatchItem.productionBatch'],
                    GoodsReceiptItem::class => ['purchaseOrderItem.purchaseOrder'],
                ]);
            }])
            ->where('qty_on_hand', '>', 0)
            ->whereHasMorph('stockable', [BatchLabel::class, GoodsReceiptItem::class], function ($q) use ($days) {
                $q->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays($days));
            })
            ->get()
            ->sortBy(fn (StockBalance $balance) => $balance->stockable->expiry_date);
    }

    /**
     * @return array{0: string, 1: string, 2: string} [kode/label, nama item, satuan]
     */
    private function nearExpiryRowData(StockBalance $balance): array
    {
        $stockable = $balance->stockable;
        $isBatchLabel = $stockable instanceof BatchLabel;
        $item = $isBatchLabel ? $stockable->productionBatchItem : $stockable->purchaseOrderItem;
        $code = $isBatchLabel ? $stockable->label_code : $stockable->purchaseOrderItem->purchaseOrder->po_number;

        return [$code, $item->item_name, $item->unit];
    }

    /**
     * Laporan Nilai Persediaan — stock_balances x biaya/unit, per outlet &
     * konsolidasi. Mencakup BatchLabel (biaya dari production_batch_items)
     * & GoodsReceiptItem (biaya dari purchase_order_items.unit_price).
     * unit_cost bisa null (belum ada harga diinput) — nilainya dihitung 0
     * tapi ditandai "belum ada harga" di view, tidak disamarkan sebagai 0
     * yang meyakinkan.
     */
    public function stockValue(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $rows = $this->stockValueRows();

        return view('scm.reports.stock-value', [
            'rows' => $rows,
            'byBranch' => $rows->groupBy('branch_name'),
            'grandTotal' => $rows->sum('value'),
            'hasIncompleteValuation' => $rows->contains(fn ($row) => $row['unit_cost'] === null),
        ]);
    }

    public function stockValueExportExcel(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $rows = $this->stockValueRows();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Persediaan');

        $header = ['Outlet', 'Label', 'Produk', 'Qty', 'Satuan', 'Biaya/Unit', 'Nilai'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $row = 2;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['branch_name'],
                $r['label_code'],
                $r['item_name'],
                $r['qty_on_hand'],
                $r['unit'],
                $r['unit_cost'],
                $r['value'],
            ], null, "A{$row}");
            $row++;
        }

        if ($row > 2) {
            $sheet->getStyle('F2:G'.($row - 1))->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'nilai-persediaan-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function stockValueExportPdf(Request $request)
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $rows = $this->stockValueRows();

        $pdf = Pdf::loadView('scm.reports.stock-value-export-pdf', [
            'rows' => $rows,
            'grandTotal' => $rows->sum('value'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('nilai-persediaan-'.now()->format('Ymd-His').'.pdf');
    }

    private function stockValueRows()
    {
        return StockBalance::with(['branch'])
            ->with(['stockable' => function ($morphTo) {
                $morphTo->morphWith([
                    BatchLabel::class => ['productionBatchItem'],
                    GoodsReceiptItem::class => ['purchaseOrderItem'],
                ]);
            }])
            ->where('qty_on_hand', '>', 0)
            ->get()
            ->map(function (StockBalance $balance) {
                $stockable = $balance->stockable;

                if ($stockable instanceof BatchLabel) {
                    $item = $stockable->productionBatchItem;
                    $itemName = $item->item_name;
                    $unit = $item->unit;
                    $labelCode = $stockable->label_code;
                    $unitCost = $item->unit_cost === null ? null : (float) $item->unit_cost;
                } else {
                    // GoodsReceiptItem — biaya/unit dari purchase_order_items.unit_price.
                    $item = $stockable->purchaseOrderItem;
                    $itemName = $item->item_name;
                    $unit = $item->unit;
                    $labelCode = $stockable->purchaseOrderItem->purchaseOrder->po_number;
                    $unitCost = (float) $item->unit_price;
                }

                return [
                    'branch_id' => $balance->branch_id,
                    'branch_name' => $balance->branch?->name,
                    'item_name' => $itemName,
                    'label_code' => $labelCode,
                    'unit' => $unit,
                    'qty_on_hand' => $balance->qty_on_hand,
                    'unit_cost' => $unitCost,
                    'value' => $unitCost === null ? null : $unitCost * $balance->qty_on_hand,
                ];
            });
    }

    public function filteredQuery(Request $request): Builder
    {
        $query = DeliveryNote::with(['fromBranch', 'toBranch', 'items'])->latest();

        if ($branchId = $request->query('branch_id')) {
            $query->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)->orWhere('to_branch_id', $branchId);
            });
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    public function summaryFor($deliveryNotes): array
    {
        return [
            'total_deliveries' => $deliveryNotes->count(),
            'total_qty_sent' => $deliveryNotes->sum(fn ($d) => $d->items->sum('qty_sent')),
            'total_qty_received' => $deliveryNotes->sum(fn ($d) => $d->items->sum('qty_received')),
            'total_discrepancy' => $deliveryNotes->where('status', DeliveryNote::STATUS_RECEIVED_WITH_DISCREPANCY)->count(),
        ];
    }
}
