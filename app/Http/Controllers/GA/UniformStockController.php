<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreUniformStockRequest;
use App\Http\Requests\GA\UniformStockMovementRequest;
use App\Http\Requests\GA\UpdateUniformStockRequest;
use App\Models\Branch;
use App\Models\GA\UniformMovement;
use App\Models\GA\UniformRecord;
use App\Models\GA\UniformStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniformStockController extends Controller
{
    public function index(Request $request): View
    {
        $branchId = $request->query('branch_id');
        $status = $request->query('status');
        $stockSearch = $request->query('stock_search');

        // --- Manajemen Stok: dikelompokkan per Tipe + Outlet + Warna, tiap
        //     grup ditampilkan sebagai satu kartu berisi baris per ukuran ---
        $stockQuery = UniformStock::with('branch');

        if ($branchId) {
            $stockQuery->where('branch_id', $branchId);
        }

        if ($status) {
            $stockQuery->where('status', $status);
        }

        if ($stockSearch) {
            $stockQuery->where(function ($q) use ($stockSearch) {
                $q->where('uniform_type', 'like', "%{$stockSearch}%")
                    ->orWhere('stock_code', 'like', "%{$stockSearch}%")
                    ->orWhere('color', 'like', "%{$stockSearch}%");
            });
        }

        $sizeOrder = array_flip(StoreUniformStockRequest::SIZES);

        $allGroups = $stockQuery->orderBy('uniform_type')->get()
            ->groupBy(fn (UniformStock $stock) => $stock->uniform_type.'|'.$stock->branch_id.'|'.($stock->color ?? ''))
            ->map(function ($items) use ($sizeOrder) {
                $first = $items->first();

                return (object) [
                    'type' => $first->uniform_type,
                    'branch' => $first->branch,
                    'color' => $first->color,
                    'items' => $items->sortBy(fn (UniformStock $i) => $sizeOrder[$i->size] ?? 99)->values(),
                    'total_available' => $items->sum('available_stock'),
                    'total_unusable' => $items->sum('unusable_stock'),
                    'is_low' => $items->contains(fn (UniformStock $i) => $i->isLowStock()),
                ];
            })
            ->values();

        $stockPage = (int) $request->query('stock_page', 1);
        $stockPerPage = 6;
        $stockGroups = new LengthAwarePaginator(
            $allGroups->forPage($stockPage, $stockPerPage)->values(),
            $allGroups->count(),
            $stockPerPage,
            $stockPage,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'stock_page']
        );

        // Daftar Pemakaian (serah-terima) sekarang halaman sendiri — lihat
        // UniformRecordController::index() / ga.uniforms.records.index.
        // Summary di bawah tetap butuh hitungan "Belum Dikembalikan" krn itu
        // cerminan stok yg sedang keluar (bukan detail record satu-satu).
        $summary = [
            'total_available' => (int) UniformStock::sum('available_stock'),
            'pending_return' => UniformRecord::where('status', UniformRecord::STATUS_ISSUED)->count(),
            'total_unusable' => (int) UniformStock::sum('unusable_stock'),
        ];

        // --- Audit trail stok, ditampilkan langsung di halaman ini ---
        $recentMovements = UniformMovement::with(['uniformStock.branch', 'createdBy'])
            ->latest()
            ->limit(8)
            ->get();

        return view('ga.uniforms.stocks.index', [
            'stockGroups' => $stockGroups,
            'summary' => $summary,
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'statusLabels' => UniformStock::statusLabels(),
            'selectedBranch' => $branchId,
            'selectedStatus' => $status,
            'stockSearch' => $stockSearch,
            'recentMovements' => $recentMovements,
            'movementTypeLabels' => UniformMovement::typeLabels(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('ga.uniforms.stocks.create', [
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'statusLabels' => UniformStock::statusLabels(),
            'sizes' => StoreUniformStockRequest::SIZES,
            'prefill' => [
                'uniform_type' => $request->query('uniform_type'),
                'branch_id' => $request->query('branch_id'),
                'color' => $request->query('color'),
            ],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $query = UniformStock::with('branch');

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $stocks = $query->orderBy('uniform_type')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris Seragam');

        $header = ['Kode', 'Tipe', 'Ukuran', 'Warna', 'Outlet', 'Kondisi', 'Tersedia', 'Tidak Layak', 'Ambang Low Stock'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $statusLabels = UniformStock::statusLabels();
        $row = 2;
        foreach ($stocks as $stock) {
            $sheet->fromArray([
                $stock->stock_code,
                $stock->uniform_type,
                $stock->size,
                $stock->color,
                $stock->branch?->name,
                $statusLabels[$stock->status] ?? $stock->status,
                $stock->available_stock,
                $stock->unusable_stock,
                $stock->low_stock_threshold,
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'inventaris-seragam-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(StoreUniformStockRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $branch = Branch::findOrFail($validated['branch_id']);

        $photoPath = null;
        if ($request->hasFile('stock_photo')) {
            $photoPath = $request->file('stock_photo')->store('uniforms/stocks', 'public');
        }

        $created = [];

        foreach ($validated['sizes'] as $row) {
            $size = trim((string) ($row['name'] ?? ''));
            $qty = (int) ($row['qty'] ?? 0);
            if ($size === '' || $qty <= 0) {
                continue;
            }

            $stock = UniformStock::firstOrNew([
                'branch_id' => $branch->id,
                'uniform_type' => $validated['uniform_type'],
                'size' => $size,
                'color' => $validated['color'] ?? null,
            ]);

            $isNew = ! $stock->exists;

            if ($isNew) {
                $stock->stock_code = UniformStock::generateStockCode($branch, $validated['uniform_type'], $size, $validated['color'] ?? null);
                $stock->status = $validated['status'];
                $stock->low_stock_threshold = $validated['low_stock_threshold'];
                $stock->available_stock = 0;
                $stock->unusable_stock = 0;
                if ($photoPath) {
                    $stock->stock_photo_path = $photoPath;
                }
            }

            $stock->available_stock += $qty;
            $stock->save();

            UniformMovement::log(
                $stock,
                UniformMovement::TYPE_RESTOCK,
                $qty,
                $isNew ? 'Stok awal varian baru' : 'Tambah stok via form Varian Baru',
                $request->user()->id,
            );

            $created[] = $stock;
        }

        $sizesAdded = collect($created)->pluck('size')->join(', ');

        return redirect()
            ->route('ga.uniforms.stocks.index')
            ->with('success', count($created)." varian '{$validated['uniform_type']}' berhasil disimpan (ukuran: {$sizesAdded}).");
    }

    public function show(UniformStock $stock): View
    {
        $stock->load('branch');

        $movements = $stock->movements()
            ->with('createdBy')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('ga.uniforms.stocks.show', [
            'stock' => $stock,
            'movements' => $movements,
            'typeLabels' => UniformMovement::typeLabels(),
        ]);
    }

    public function edit(UniformStock $stock): View
    {
        return view('ga.uniforms.stocks.edit', [
            'stock' => $stock,
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'statusLabels' => UniformStock::statusLabels(),
        ]);
    }

    public function update(UpdateUniformStockRequest $request, UniformStock $stock): RedirectResponse
    {
        $validated = $request->validated();
        $branch = Branch::findOrFail($validated['branch_id']);

        $stock->fill($validated);
        $stock->stock_code = UniformStock::generateStockCode(
            $branch,
            $validated['uniform_type'],
            $validated['size'] ?? null,
            $validated['color'] ?? null,
        );

        if ($request->hasFile('stock_photo')) {
            if ($stock->stock_photo_path) {
                Storage::disk('public')->delete($stock->stock_photo_path);
            }
            $stock->stock_photo_path = $request->file('stock_photo')->store('uniforms/stocks', 'public');
        }

        $stock->save();

        return redirect()
            ->route('ga.uniforms.stocks.show', $stock)
            ->with('success', "Varian seragam {$stock->stock_code} berhasil diperbarui.");
    }

    public function destroy(UniformStock $stock): RedirectResponse
    {
        if ($stock->stock_photo_path) {
            Storage::disk('public')->delete($stock->stock_photo_path);
        }

        $stock->delete();

        return redirect()
            ->route('ga.uniforms.stocks.index')
            ->with('success', 'Varian seragam berhasil dihapus.');
    }

    /**
     * Hapus satu grup (Tipe + Outlet + Warna) sekaligus — semua ukuran di
     * dalamnya, dipakai oleh tombol hapus per kartu di Manajemen Stok.
     */
    public function destroyGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'uniform_type' => ['required', 'string'],
            'branch_id' => ['required', 'exists:branches,id'],
            'color' => ['nullable', 'string'],
        ]);

        $stocks = UniformStock::where('uniform_type', $validated['uniform_type'])
            ->where('branch_id', $validated['branch_id'])
            ->where('color', $validated['color'] ?? null)
            ->get();

        foreach ($stocks as $stock) {
            if ($stock->stock_photo_path) {
                Storage::disk('public')->delete($stock->stock_photo_path);
            }
            $stock->delete();
        }

        return redirect()
            ->route('ga.uniforms.stocks.index')
            ->with('success', "Grup '{$validated['uniform_type']}' berhasil dihapus ({$stocks->count()} varian ukuran).");
    }

    public function restock(UniformStockMovementRequest $request, UniformStock $stock): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');

        $stock->available_stock += $quantity;
        $stock->save();

        UniformMovement::log($stock, UniformMovement::TYPE_RESTOCK, $quantity, $request->validated('notes'), $request->user()->id);

        return redirect()
            ->back()
            ->with('success', "Restock {$quantity} pcs berhasil dicatat.");
    }

    public function issue(UniformStockMovementRequest $request, UniformStock $stock): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');

        $stock->available_stock -= $quantity;
        $stock->save();

        UniformMovement::log($stock, UniformMovement::TYPE_ISSUE, -$quantity, $request->validated('notes'), $request->user()->id);

        return redirect()
            ->route('ga.uniforms.stocks.show', $stock)
            ->with('success', "Issue {$quantity} pcs berhasil dicatat.");
    }

    public function adjustment(UniformStockMovementRequest $request, UniformStock $stock): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');

        $stock->available_stock += $quantity;
        $stock->save();

        UniformMovement::log($stock, UniformMovement::TYPE_ADJUSTMENT, $quantity, $request->validated('notes'), $request->user()->id);

        return redirect()
            ->route('ga.uniforms.stocks.show', $stock)
            ->with('success', 'Penyesuaian stok berhasil dicatat.');
    }

    public function disposal(UniformStockMovementRequest $request, UniformStock $stock): RedirectResponse
    {
        $quantity = (int) $request->validated('quantity');

        $stock->unusable_stock -= $quantity;
        $stock->save();

        UniformMovement::log($stock, UniformMovement::TYPE_DISPOSAL, -$quantity, $request->validated('notes'), $request->user()->id);

        return redirect()
            ->route('ga.uniforms.stocks.show', $stock)
            ->with('success', "Disposal {$quantity} pcs berhasil dicatat.");
    }
}
