<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreUniformStockRequest;
use App\Http\Requests\GA\UniformStockMovementRequest;
use App\Http\Requests\GA\UpdateUniformStockRequest;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\GA\UniformMovement;
use App\Models\GA\UniformRecord;
use App\Models\GA\UniformStock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
        $stockQuery = UniformStock::with(['branch', 'branchLocation']);

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
                    'branchLocation' => $first->branchLocation,
                    'color' => $first->color,
                    'photo_path' => $items->first(fn (UniformStock $i) => $i->stock_photo_path)?->stock_photo_path,
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
        $recentMovements = UniformMovement::with(['uniformStock.branch', 'uniformStock.branchLocation', 'createdBy'])
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
        $prefill = [
            'uniform_type' => $request->query('uniform_type'),
            'branch_id' => $request->query('branch_id'),
            'color' => $request->query('color'),
        ];

        // Kalau form ini dibuka dari tombol "+" grup yang sudah ada (lihat
        // index), tampilkan sisa stok tiap ukuran yang sudah ada — cuma
        // preview, TIDAK bisa diedit lewat sini (edit stok per ukuran lewat
        // halaman detail varian masing-masing).
        $existingStock = collect();
        $currentPhotoPath = null;
        if ($prefill['uniform_type'] && $prefill['branch_id']) {
            $sizeOrder = array_flip(StoreUniformStockRequest::SIZES);
            $existingStock = UniformStock::where('uniform_type', $prefill['uniform_type'])
                ->where('branch_id', $prefill['branch_id'])
                ->where('color', $prefill['color'] ?: null)
                ->get()
                ->sortBy(fn (UniformStock $i) => $sizeOrder[$i->size] ?? 99)
                ->values();
            $currentPhotoPath = $existingStock->first(fn (UniformStock $i) => $i->stock_photo_path)?->stock_photo_path;
        }

        // Dipakai frontend utk cek "nama Tipe Seragam sudah ada di outlet ini"
        // secara live saat mengisi form kosong (bukan form prefill dari ikon
        // "+" grup yang sudah ada — di situ duplikat memang disengaja).
        $typesByBranch = UniformStock::select('branch_id', 'uniform_type')
            ->distinct()
            ->get()
            ->groupBy(fn (UniformStock $row) => (string) $row->branch_id)
            ->map(fn ($rows) => $rows->pluck('uniform_type')->values());

        return view('ga.uniforms.stocks.create', [
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
            'statusLabels' => UniformStock::statusLabels(),
            'sizes' => StoreUniformStockRequest::SIZES,
            'prefill' => $prefill,
            'existingStock' => $existingStock,
            'currentPhotoPath' => $currentPhotoPath,
            'typesByBranch' => $typesByBranch,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $stocks = $this->filteredQuery($request)->orderBy('uniform_type')->get();

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

    public function exportPdf(Request $request)
    {
        $stocks = $this->filteredQuery($request)->orderBy('uniform_type')->get();

        $pdf = Pdf::loadView('ga.uniforms.stocks.export-pdf', [
            'stocks' => $stocks,
            'statusLabels' => UniformStock::statusLabels(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('inventaris-seragam-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Query dasar yang dipakai bersama oleh kedua export — supaya hasilnya
     * selalu konsisten dengan filter yang sedang aktif di layar index().
     */
    private function filteredQuery(Request $request)
    {
        $query = UniformStock::with('branch');

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('stock_search')) {
            $query->where(function ($q) use ($search) {
                $q->where('uniform_type', 'like', "%{$search}%")
                    ->orWhere('stock_code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function store(StoreUniformStockRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $branch = Branch::findOrFail($validated['branch_id']);
        $threshold = $validated['low_stock_threshold'] ?? 0;

        $photoPath = null;
        if ($request->hasFile('stock_photo')) {
            $photoPath = $request->file('stock_photo')->store('uniforms/stocks', 'public');
        }

        // Grup yang sama (tipe+outlet+warna) yang SUDAH ADA sebelum submit
        // ini — dipakai supaya Ambang Low Stock & Foto Varian (field yang
        // "berlaku sama untuk semua ukuran") ikut diterapkan ke ukuran yang
        // sudah ada, bukan cuma ukuran baru yang ditambahkan di submit ini.
        $existingGroupStocks = UniformStock::where('branch_id', $branch->id)
            ->where('uniform_type', $validated['uniform_type'])
            ->where('color', $validated['color'] ?? null)
            ->get();

        $created = [];
        $touchedIds = [];

        $applyMetadata = function (UniformStock $stock) use ($threshold, $photoPath, $validated) {
            $stock->low_stock_threshold = $threshold;
            $stock->branch_location_id = $validated['branch_location_id'] ?? null;
            if ($photoPath && $stock->stock_photo_path && $stock->stock_photo_path !== $photoPath) {
                Storage::disk('public')->delete($stock->stock_photo_path);
            }
            if ($photoPath) {
                $stock->stock_photo_path = $photoPath;
            }
        };

        // Semua perubahan dibungkus transaction + try/catch — generateStockCode()
        // sudah menjamin kode tidak bentrok sama varian lain (lihat model),
        // jadi catch ini cuma jaring pengaman utk error DB lain yang tidak
        // terduga. Kalau salah satu baris gagal disimpan, batalkan semuanya
        // dan tampilkan pesan yang jelas ke user, bukan biarkan sebagian baris
        // kesave lalu crash jadi generic server error di baris berikutnya.
        try {
            DB::transaction(function () use ($validated, $branch, $applyMetadata, $request, $existingGroupStocks, &$created, &$touchedIds) {
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
                        // Seragam yang baru didata pasti dalam kondisi baik — tidak
                        // ada pilihan status saat create, cuma muncul lagi saat
                        // pengembalian (lihat UniformRecord::return_condition).
                        $stock->status = UniformStock::STATUS_BAGUS;
                        $stock->available_stock = 0;
                        $stock->unusable_stock = 0;
                    }

                    $applyMetadata($stock);
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
                    if (! $isNew) {
                        $touchedIds[] = $stock->id;
                    }
                }

                // Sisa ukuran dalam grup yang sama tapi tidak diisi qty-nya sama
                // sekali di form ini — tetap ikut menerima update Ambang Low Stock
                // & Foto Varian (supaya field itu benar konsisten se-grup), dan ini
                // juga jalur "edit metadata saja tanpa nambah stok" kalau SEMUA
                // baris ukuran dikosongkan (izinkan oleh StoreUniformStockRequest
                // selama grupnya memang sudah ada).
                foreach ($existingGroupStocks as $stock) {
                    if (in_array($stock->id, $touchedIds, true)) {
                        continue;
                    }
                    $applyMetadata($stock);
                    if ($stock->isDirty()) {
                        $stock->save();
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'uniform_type' => 'Gagal menyimpan varian ini karena kesalahan sistem. Coba lagi, atau hubungi IT kalau masih gagal.',
            ]);
        }

        if (empty($created)) {
            return redirect()
                ->route('ga.uniforms.stocks.index')
                ->with('success', "Varian '{$validated['uniform_type']}' berhasil diperbarui.");
        }

        $sizesAdded = collect($created)->pluck('size')->join(', ');

        return redirect()
            ->route('ga.uniforms.stocks.index')
            ->with('success', count($created)." varian '{$validated['uniform_type']}' berhasil disimpan (ukuran: {$sizesAdded}).");
    }

    public function show(UniformStock $stock): View
    {
        $stock->load(['branch', 'branchLocation']);

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
            'branchLocations' => BranchLocation::groupedByBranch(),
            'statusLabels' => UniformStock::statusLabels(),
        ]);
    }

    public function update(UpdateUniformStockRequest $request, UniformStock $stock): RedirectResponse
    {
        $validated = $request->validated();
        $validated['low_stock_threshold'] ??= 0;
        $branch = Branch::findOrFail($validated['branch_id']);

        // Cek tabrakan combo (outlet+tipe+ukuran+warna) dulu — ini constraint
        // unique yang SEBENARNYA membatasi (uniform_stocks_variant_unique).
        // stock_code sendiri TIDAK perlu dicek manual — generateStockCode()
        // sudah auto-hindari tabrakannya sendiri lewat suffix -2/-3/dst.
        $collision = UniformStock::where('branch_id', $validated['branch_id'])
            ->where('uniform_type', $validated['uniform_type'])
            ->where('size', $validated['size'] ?? null)
            ->where('color', $validated['color'] ?? null)
            ->where('id', '!=', $stock->id)
            ->first();

        if ($collision) {
            return back()->withInput()->withErrors([
                'uniform_type' => "Kombinasi Tipe Seragam + Outlet + Ukuran + Warna ini sudah dipakai varian lain ({$collision->stock_code}). Ubah salah satu supaya tidak sama.",
            ]);
        }

        // $stock->id dikirim sebagai $ignoreId supaya varian ini tidak
        // dianggap "bentrok" sama dirinya sendiri saat kode-nya tidak berubah.
        $newStockCode = UniformStock::generateStockCode(
            $branch,
            $validated['uniform_type'],
            $validated['size'] ?? null,
            $validated['color'] ?? null,
            $stock->id,
        );

        $stock->fill($validated);
        $stock->stock_code = $newStockCode;

        if ($request->hasFile('stock_photo')) {
            if ($stock->stock_photo_path) {
                Storage::disk('public')->delete($stock->stock_photo_path);
            }
            $stock->stock_photo_path = $request->file('stock_photo')->store('uniforms/stocks', 'public');
        }

        try {
            $stock->save();
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'color' => 'Gagal menyimpan perubahan varian ini. Coba lagi, atau hubungi IT kalau masih gagal.',
            ]);
        }

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

    /**
     * Detail satu GRUP varian (semua ukuran sekaligus) — ini tujuan klik
     * kartu di halaman daftar (bukan klik per-baris ukuran lagi). Tiap
     * ukuran di sini tetap link ke halaman detail per-variannya sendiri
     * (route show() biasa) supaya aksi Restock & Riwayat Movement per
     * ukuran tetap bisa diakses, cuma sekarang lewat sini dulu.
     */
    public function showGroup(Request $request): View
    {
        $validated = $request->validate([
            'uniform_type' => ['required', 'string'],
            'branch_id' => ['required', 'exists:branches,id'],
            'color' => ['nullable', 'string'],
        ]);

        $items = UniformStock::with(['branch', 'branchLocation'])
            ->where('uniform_type', $validated['uniform_type'])
            ->where('branch_id', $validated['branch_id'])
            ->where('color', $validated['color'] ?? null)
            ->get();

        abort_if($items->isEmpty(), 404);

        $sizeOrder = array_flip(StoreUniformStockRequest::SIZES);
        $items = $items->sortBy(fn (UniformStock $i) => $sizeOrder[$i->size] ?? 99)->values();

        return view('ga.uniforms.stocks.show-group', [
            'items' => $items,
            'first' => $items->first(),
            'totalAvailable' => $items->sum('available_stock'),
            'totalUnusable' => $items->sum('unusable_stock'),
        ]);
    }

    /**
     * Edit metadata satu GRUP varian sekaligus (Tipe Seragam/Outlet/Warna/
     * Ambang Low Stock/Foto Varian) — supaya user tidak perlu membuka form
     * edit satu-satu per ukuran kalau cuma mau benarkan nama/outlet/warna.
     * Stok (available_stock/unusable_stock) sengaja TIDAK bisa diubah di
     * sini — itu tetap cuma lewat Restock atau alur serah-terima/pengembalian.
     */
    public function editGroup(Request $request): View
    {
        $validated = $request->validate([
            'uniform_type' => ['required', 'string'],
            'branch_id' => ['required', 'exists:branches,id'],
            'color' => ['nullable', 'string'],
        ]);

        $items = UniformStock::where('uniform_type', $validated['uniform_type'])
            ->where('branch_id', $validated['branch_id'])
            ->where('color', $validated['color'] ?? null)
            ->orderBy('size')
            ->get();

        abort_if($items->isEmpty(), 404);

        return view('ga.uniforms.stocks.edit-group', [
            'items' => $items,
            'first' => $items->first(),
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
        ]);
    }

    public function updateGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'old_uniform_type' => ['required', 'string'],
            'old_branch_id' => ['required', 'exists:branches,id'],
            'old_color' => ['nullable', 'string'],

            'uniform_type' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'branch_location_id' => [
                'nullable',
                Rule::exists('branch_locations', 'id')->where(
                    fn ($q) => $q->where('branch_id', $request->input('branch_id'))->where('is_active', true)
                ),
            ],
            'color' => ['required', 'string', 'max:100'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'stock_photo' => ['nullable', 'image', 'max:4096'],
        ], [
            'color.required' => 'Warna wajib diisi.',
        ]);

        $items = UniformStock::where('uniform_type', $validated['old_uniform_type'])
            ->where('branch_id', $validated['old_branch_id'])
            ->where('color', $validated['old_color'] ?? null)
            ->get();

        abort_if($items->isEmpty(), 404);

        $newBranch = Branch::findOrFail($validated['branch_id']);
        $threshold = $validated['low_stock_threshold'] ?? 0;

        $photoPath = null;
        if ($request->hasFile('stock_photo')) {
            $photoPath = $request->file('stock_photo')->store('uniforms/stocks', 'public');
        }

        // Cek tabrakan combo (outlet+tipe+ukuran+warna) utk SEMUA baris dulu
        // sebelum menyimpan apa pun — ini constraint unique yang SEBENARNYA
        // membatasi (uniform_stocks_variant_unique), BUKAN stock_code (yang
        // sudah auto-hindari tabrakannya sendiri lewat suffix -2/-3/dst di
        // generateStockCode()). Supaya kalau satu ukuran bentrok, tidak ada
        // baris lain yang sudah kesave duluan.
        foreach ($items as $item) {
            $collision = UniformStock::where('branch_id', $newBranch->id)
                ->where('uniform_type', $validated['uniform_type'])
                ->where('size', $item->size)
                ->where('color', $validated['color'])
                ->where('id', '!=', $item->id)
                ->first();

            if ($collision) {
                return back()->withInput()->withErrors([
                    'uniform_type' => "Ukuran {$item->size}: kombinasi Tipe Seragam + Outlet + Warna yang baru ini sudah dipakai varian lain ({$collision->stock_code}). Ubah salah satu supaya tidak sama.",
                ]);
            }
        }

        try {
            DB::transaction(function () use ($items, $validated, $newBranch, $threshold, $photoPath) {
                foreach ($items as $item) {
                    if ($photoPath && $item->stock_photo_path && $item->stock_photo_path !== $photoPath) {
                        Storage::disk('public')->delete($item->stock_photo_path);
                    }

                    $item->uniform_type = $validated['uniform_type'];
                    $item->branch_id = $newBranch->id;
                    $item->branch_location_id = $validated['branch_location_id'] ?? null;
                    $item->color = $validated['color'];
                    $item->low_stock_threshold = $threshold;
                    if ($photoPath) {
                        $item->stock_photo_path = $photoPath;
                    }
                    $item->stock_code = UniformStock::generateStockCode($newBranch, $validated['uniform_type'], $item->size, $validated['color']);
                    $item->save();
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'uniform_type' => 'Gagal menyimpan perubahan grup ini. Coba lagi, atau hubungi IT kalau masih gagal.',
            ]);
        }

        return redirect()
            ->route('ga.uniforms.stocks.index')
            ->with('success', "Grup '{$validated['uniform_type']}' berhasil diperbarui ({$items->count()} ukuran).");
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

}
