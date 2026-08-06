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

        return $pdf->download('rekap-scm-'.now()->format('Ymd-His').'.pdf');
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

        $balances = StockBalance::with(['branch'])
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
            ->sortBy(fn (StockBalance $balance) => $balance->stockable->expiry_date)
            ->groupBy('branch_id');

        return view('scm.reports.near-expiry', [
            'days' => $days,
            'groups' => $balances,
        ]);
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

        $rows = StockBalance::with(['branch'])
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

        return view('scm.reports.stock-value', [
            'rows' => $rows,
            'byBranch' => $rows->groupBy('branch_name'),
            'grandTotal' => $rows->sum('value'),
            'hasIncompleteValuation' => $rows->contains(fn ($row) => $row['unit_cost'] === null),
        ]);
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
