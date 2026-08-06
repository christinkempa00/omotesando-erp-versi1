<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\ReturnUniformRecordRequest;
use App\Http\Requests\GA\StoreUniformRecordRequest;
use App\Models\Branch;
use App\Models\GA\UniformMovement;
use App\Models\GA\UniformRecord;
use App\Models\GA\UniformStock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class UniformRecordController extends Controller
{
    public function index(Request $request): View
    {
        $query = UniformRecord::with(['branch', 'uniformStock']);

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
                    ->orWhere('uniform_type', 'like', "%{$search}%");
            });
        }

        $records = $query->latest()->paginate(15)->withQueryString();

        return view('ga.uniforms.records.index', [
            'records' => $records,
            'statusLabels' => UniformRecord::statusLabels(),
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'selectedStatus' => $status,
            'selectedBranch' => $branchId,
            'search' => $search,
        ]);
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
            'stockTree' => $stockTree,
        ]);
    }

    public function store(StoreUniformRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $stock = UniformStock::findOrFail($validated['uniform_stock_id']);

        if ($stock->available_stock < 1) {
            return back()->withInput()->withErrors(['uniform_stock_id' => 'Stok varian ini sudah habis.']);
        }

        $record = new UniformRecord([
            'employee_name' => $validated['employee_name'],
            'branch_id' => $stock->branch_id,
            'uniform_type' => $stock->uniform_type,
            'size' => $stock->size,
            'color' => $stock->color,
            'uniform_stock_id' => $stock->id,
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
            $record->signature_path = $this->storeSignature($signatureData);
        }

        $record->save();

        $stock->available_stock -= 1;
        $stock->save();

        UniformMovement::log(
            $stock,
            UniformMovement::TYPE_ISSUE,
            -1,
            "Issue ke {$record->employee_name}",
            $request->user()->id,
            $record->record_code,
        );

        return redirect()
            ->route('ga.uniforms.records.show', $record)
            ->with('success', "Serah-terima {$record->record_code} berhasil dicatat.");
    }

    public function show(UniformRecord $record): View
    {
        $record->load(['branch', 'uniformStock', 'createdBy']);

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
        $record->load(['branch', 'uniformStock', 'createdBy']);

        $pdf = Pdf::loadView('ga.uniforms.records.document-pdf', [
            'record' => $record,
            'conditionLabels' => UniformRecord::conditionLabels(),
        ])->setPaper('a4', 'portrait');

        $filename = 'Serah-Terima-'.$record->record_code.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Decode signature_data (data URI base64 dari canvas) jadi file PNG.
     */
    private function storeSignature(string $signatureData): ?string
    {
        if (! preg_match('/^data:image\/png;base64,(.+)$/', $signatureData, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[1]);

        if ($binary === false) {
            return null;
        }

        $path = 'uniforms/signatures/'.Str::random(32).'.png';
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

        $record->status = UniformRecord::STATUS_RETURNED;
        $record->return_date = $validated['return_date'];
        $record->return_condition = $validated['return_condition'];
        $record->return_notes = $validated['return_notes'] ?? null;
        $record->save();

        // Sinkronkan ke stok: kondisi baik kembali ke available, rusak masuk
        // unusable, hilang tidak menyentuh stok sama sekali (barang tidak kembali).
        if ($record->uniformStock && $validated['return_condition'] !== UniformRecord::CONDITION_LOST) {
            $stock = $record->uniformStock;

            if ($validated['return_condition'] === UniformRecord::CONDITION_GOOD) {
                $stock->available_stock += 1;
            } else {
                $stock->unusable_stock += 1;
            }

            $stock->save();

            UniformMovement::log(
                $stock,
                UniformMovement::TYPE_RETURN,
                1,
                "Return dari {$record->employee_name} (".UniformRecord::conditionLabels()[$validated['return_condition']].')',
                $request->user()->id,
                $record->record_code,
            );
        }

        return redirect()
            ->route('ga.uniforms.records.show', $record)
            ->with('success', "Seragam dari {$record->employee_name} berhasil ditandai dikembalikan.");
    }
}
