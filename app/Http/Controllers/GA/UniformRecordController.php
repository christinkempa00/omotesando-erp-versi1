<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\ReturnUniformRecordRequest;
use App\Http\Requests\GA\StoreUniformRecordRequest;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\GA\UniformMovement;
use App\Models\GA\UniformRecord;
use App\Models\GA\UniformRecordItem;
use App\Models\GA\UniformStock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniformRecordController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $branchId = $request->query('branch_id');
        $search = $request->query('search');

        $records = $this->filteredQuery($request)->latest()->paginate(15)->withQueryString();

        return view('ga.uniforms.records.index', [
            'records' => $records,
            'statusLabels' => UniformRecord::statusLabels(),
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'selectedStatus' => $status,
            'selectedBranch' => $branchId,
            'search' => $search,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $records = $this->filteredQuery($request)->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Serah Terima Seragam');

        $header = ['Kode', 'Karyawan', 'Item', 'Outlet', 'Tgl Serah', 'Status', 'Tgl Kembali', 'Kondisi Kembali'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $statusLabels = UniformRecord::statusLabels();
        $conditionLabels = UniformRecord::conditionLabels();
        $row = 2;
        foreach ($records as $record) {
            $sheet->fromArray([
                $record->record_code,
                $record->employee_name,
                $record->summaryLabel(),
                $record->outletLabel(),
                $record->issue_date?->format('d/m/Y'),
                $statusLabels[$record->status] ?? $record->status,
                $record->return_date?->format('d/m/Y'),
                $record->return_condition ? ($conditionLabels[$record->return_condition] ?? $record->return_condition) : null,
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'serah-terima-seragam-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $records = $this->filteredQuery($request)->latest()->get();

        $pdf = Pdf::loadView('ga.uniforms.records.export-pdf', [
            'records' => $records,
            'statusLabels' => UniformRecord::statusLabels(),
            'conditionLabels' => UniformRecord::conditionLabels(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('serah-terima-seragam-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Query dasar yang dipakai bersama index() & kedua export — supaya hasil
     * export selalu konsisten dengan filter yang sedang aktif di layar index().
     */
    private function filteredQuery(Request $request)
    {
        $query = UniformRecord::with(['branch', 'branchLocation', 'uniformStock', 'items']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('record_code', 'like', "%{$search}%")
                    ->orWhere('uniform_type', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($iq) => $iq->where('uniform_type', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function create(): View
    {
        $stocks = UniformStock::with('branch')->where('available_stock', '>', 0)->orderBy('uniform_type')->get();

        // --- Pohon Outlet -> Jenis Seragam -> Warna -> Ukuran, dipakai Alpine
        //     utk dropdown berjenjang supaya user tidak perlu menelusuri satu
        //     list varian yang datar & makin panjang seiring bertambahnya data ---
        $stockTree = $stocks->groupBy('branch_id')->map(function ($byBranch) {
            return $byBranch->groupBy('uniform_type')->map(function ($byType) {
                return $byType->groupBy(fn (UniformStock $s) => $s->color ?: '-')->map(function ($byColor) {
                    return $byColor->map(fn (UniformStock $s) => [
                        'id' => $s->id,
                        'size' => $s->size ?: '-',
                        'available' => $s->available_stock,
                    ])->values();
                });
            });
        });

        $branches = Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS)
            ->filter(fn (Branch $branch) => $stockTree->has($branch->id))
            ->values();

        return view('ga.uniforms.records.create', [
            'branches' => $branches,
            'branchLocations' => BranchLocation::groupedByBranch(),
            'stockTree' => $stockTree,
        ]);
    }

    public function store(StoreUniformRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $stocks = UniformStock::whereIn('id', collect($validated['items'])->pluck('uniform_stock_id'))
            ->get()
            ->keyBy('id');

        // Divalidasi SEBELUM transaction dimulai — outlet tiap item harus sama
        // dgn outlet yg dipilih di header (outlet cuma dipilih sekali di form),
        // dan stok tiap item harus cukup. Kalau salah satu gagal, tolak
        // semuanya sebelum baris lain sempat tersimpan.
        foreach ($validated['items'] as $itemInput) {
            $stock = $stocks->get($itemInput['uniform_stock_id']);

            if (! $stock || (int) $stock->branch_id !== (int) $validated['branch_id']) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Salah satu item tidak sesuai dengan Outlet yang dipilih.',
                ]);
            }

            if ($stock->available_stock < $itemInput['qty']) {
                throw ValidationException::withMessages([
                    'items' => "Stok {$stock->stock_code} tidak cukup (tersedia {$stock->available_stock}).",
                ]);
            }
        }

        $firstStock = $stocks->get($validated['items'][0]['uniform_stock_id']);

        $record = DB::transaction(function () use ($validated, $request, $stocks, $firstStock) {
            $record = new UniformRecord([
                'employee_name' => $validated['employee_name'],
                'issued_by_name' => $validated['issued_by_name'],
                'branch_id' => $validated['branch_id'],
                'branch_location_id' => $validated['branch_location_id'] ?? null,
                // Cache "item pertama" — kompatibilitas kolom lama, lihat
                // catatan di UniformRecord::summaryLabel()/isItemized().
                'uniform_type' => $firstStock->uniform_type,
                'size' => $firstStock->size,
                'color' => $firstStock->color,
                'uniform_stock_id' => $firstStock->id,
                'issue_date' => $validated['issue_date'],
                'issue_notes' => $validated['issue_notes'] ?? null,
                'status' => UniformRecord::STATUS_ISSUED,
            ]);
            $record->record_code = UniformRecord::generateRecordCode();
            $record->created_by = $request->user()->id;

            if ($request->hasFile('issue_photo')) {
                $record->issue_photo_path = $request->file('issue_photo')->store('uniforms/issue-photos', 'public');
            }

            if ($signatureData = $validated['signature_data'] ?? null) {
                $record->signature_path = $this->storeSignature($signatureData, 'uniforms/signatures');
            }

            if ($issuedBySignatureData = $validated['issued_by_signature_data'] ?? null) {
                $record->issued_by_signature_path = $this->storeSignature($issuedBySignatureData, 'uniforms/signatures');
            }

            $record->save();

            foreach ($validated['items'] as $itemInput) {
                $stock = $stocks->get($itemInput['uniform_stock_id']);

                $record->items()->create([
                    'uniform_stock_id' => $stock->id,
                    'uniform_type' => $stock->uniform_type,
                    'size' => $stock->size,
                    'color' => $stock->color,
                    'qty' => $itemInput['qty'],
                    // Seragam yang baru diserahkan pasti dalam kondisi baik —
                    // tidak ada input kondisi dari user saat serah terima,
                    // sama seperti konvensi UniformStock (lihat store() di
                    // UniformStockController). Kondisi barunya baru relevan
                    // & bisa berubah saat PENGEMBALIAN (return_condition).
                    'item_condition' => 'Baru',
                    'item_notes' => $itemInput['item_notes'] ?? null,
                ]);

                $stock->available_stock -= $itemInput['qty'];
                $stock->save();

                UniformMovement::log(
                    $stock,
                    UniformMovement::TYPE_ISSUE,
                    -$itemInput['qty'],
                    "Issue to {$record->employee_name}",
                    $request->user()->id,
                    $record->record_code,
                );
            }

            return $record;
        });

        return redirect()
            ->route('ga.uniforms.records.show', $record)
            ->with('success', "Serah-terima {$record->record_code} berhasil dicatat.");
    }

    public function show(UniformRecord $record): View
    {
        $record->load(['branch', 'branchLocation', 'uniformStock', 'createdBy', 'items.uniformStock']);

        return view('ga.uniforms.records.show', [
            'record' => $record,
            'statusLabels' => UniformRecord::statusLabels(),
            'conditionLabels' => UniformRecord::conditionLabels(),
        ]);
    }

    /**
     * Dokumen Berita Acara Serah Terima (PDF) — dibuat dari data serah-terima
     * + tanda tangan digital yang direkam saat form diisi.
     */
    public function document(UniformRecord $record): Response
    {
        $record->load(['branch', 'branchLocation', 'createdBy', 'items']);

        $pdf = Pdf::loadView('ga.uniforms.records.document-pdf', [
            'record' => $record,
        ])->setPaper('a4', 'portrait');

        $filename = 'Issuance-'.$record->record_code.'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Dokumen Pemeriksaan Pengembalian Barang (PDF) — hanya tersedia setelah
     * record ditandai dikembalikan (lihat markReturned()).
     */
    public function returnDocument(UniformRecord $record): Response
    {
        abort_unless($record->status === UniformRecord::STATUS_RETURNED, 404);

        $record->load(['branch', 'branchLocation', 'createdBy']);

        $pdf = Pdf::loadView('ga.uniforms.records.return-document-pdf', [
            'record' => $record,
        ])->setPaper('a4', 'portrait');

        $filename = 'Return-'.$record->record_code.'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Decode signature_data (data URI base64 dari canvas) jadi file PNG.
     */
    private function storeSignature(string $signatureData, string $directory = 'uniforms/signatures'): ?string
    {
        if (! preg_match('/^data:image\/png;base64,(.+)$/', $signatureData, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1]);

        if ($binary === false) {
            return null;
        }

        $path = $directory.'/'.Str::random(32).'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public function edit(UniformRecord $record): View
    {
        return view('ga.uniforms.records.edit', [
            'record' => $record,
        ]);
    }

    public function update(Request $request, UniformRecord $record): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => ['required', 'string', 'max:255'],
            'issued_by_name' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'issue_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $record->fill($validated);
        $record->save();

        return redirect()
            ->route('ga.uniforms.records.show', $record)
            ->with('success', "Serah-terima {$record->record_code} berhasil diperbarui.");
    }

    public function destroy(UniformRecord $record): RedirectResponse
    {
        if ($record->issue_photo_path) {
            Storage::disk('public')->delete($record->issue_photo_path);
        }
        if ($record->signature_path) {
            Storage::disk('public')->delete($record->signature_path);
        }
        if ($record->issued_by_signature_path) {
            Storage::disk('public')->delete($record->issued_by_signature_path);
        }
        if ($record->return_signature_path) {
            Storage::disk('public')->delete($record->return_signature_path);
        }
        if ($record->received_by_signature_path) {
            Storage::disk('public')->delete($record->received_by_signature_path);
        }

        $record->delete();

        return redirect()
            ->route('ga.uniforms.records.index')
            ->with('success', 'Catatan serah-terima berhasil dihapus.');
    }

    public function markReturned(ReturnUniformRecordRequest $request, UniformRecord $record): RedirectResponse
    {
        if ($record->status === UniformRecord::STATUS_RETURNED) {
            return back()->withErrors(['status' => 'Seragam ini sudah ditandai dikembalikan.']);
        }

        $validated = $request->validated();
        $record->load(['items.uniformStock', 'uniformStock']);

        DB::transaction(function () use ($record, $validated, $request) {
            $record->status = UniformRecord::STATUS_RETURNED;
            $record->return_date = $validated['return_date'];
            $record->return_condition = $validated['return_condition'];
            $record->return_notes = $validated['return_notes'] ?? null;
            $record->returned_by_name = $validated['returned_by_name'];
            $record->received_by_name = $validated['received_by_name'];
            $record->qty_sesuai = $validated['qty_sesuai'];
            $record->qty_sesuai_notes = $validated['qty_sesuai_notes'] ?? null;
            $record->spesifikasi_sesuai = $validated['spesifikasi_sesuai'];
            $record->spesifikasi_sesuai_notes = $validated['spesifikasi_sesuai_notes'] ?? null;
            $record->kondisi_sesuai = $validated['kondisi_sesuai'];
            $record->kondisi_sesuai_notes = $validated['kondisi_sesuai_notes'] ?? null;

            if ($signatureData = $validated['return_signature_data'] ?? null) {
                $record->return_signature_path = $this->storeSignature($signatureData, 'uniforms/return-signatures');
            }

            if ($receivedBySignatureData = $validated['received_by_signature_data'] ?? null) {
                $record->received_by_signature_path = $this->storeSignature($receivedBySignatureData, 'uniforms/return-signatures');
            }

            $record->save();

            // Sinkronkan ke stok: kondisi bagus kembali ke available, rusak
            // masuk unusable. Kondisi berlaku utk SELURUH batch (bukan
            // per-item) — sesuai dok. Pemeriksaan Pengembalian yg tidak
            // merinci per barang. (Barang yang hilang/tidak kembali tidak
            // diproses lewat aksi ini sama sekali — lihat catatan di
            // UniformRecord::conditionLabels().)

            // Record baru (itemized) pakai items masing2 dgn qty-nya sendiri;
            // record lama (pre-fitur-ini) pakai uniformStock header, qty=1.
            $entries = $record->isItemized()
                ? $record->items
                    ->filter(fn (UniformRecordItem $item) => $item->uniformStock !== null)
                    ->map(fn (UniformRecordItem $item) => ['stock' => $item->uniformStock, 'qty' => $item->qty])
                : ($record->uniformStock ? collect([['stock' => $record->uniformStock, 'qty' => 1]]) : collect());

            foreach ($entries as $entry) {
                $stock = $entry['stock'];
                $qty = $entry['qty'];

                if ($validated['return_condition'] === UniformRecord::CONDITION_GOOD) {
                    $stock->available_stock += $qty;
                } else {
                    $stock->unusable_stock += $qty;
                }

                $stock->save();

                UniformMovement::log(
                    $stock,
                    UniformMovement::TYPE_RETURN,
                    $qty,
                    "Return from {$record->employee_name} (".UniformRecord::conditionLabels()[$validated['return_condition']].')',
                    $request->user()->id,
                    $record->record_code,
                );
            }
        });

        return redirect()
            ->route('ga.uniforms.records.show', $record)
            ->with('success', "Seragam dari {$record->employee_name} berhasil ditandai dikembalikan.");
    }
}
