<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Http\Requests\SCM\StoreProductionBatchRequest;
use App\Models\Role;
use App\Models\SCM\MaterialRequest;
use App\Models\SCM\ProductionBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch Produksi — dibuat Produksi dari MaterialRequest yang sudah approved,
 * disetujui/ditolak Admin (1 step). Setelah approved, Gudang bisa cetak label
 * (lihat BatchLabelController) & bikin surat jalan (DeliveryNoteController).
 */
class ProductionBatchController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->hasRole(Role::PRODUKSI, Role::GUDANG, Role::ADMIN), 403);

        $batches = $this->filteredQuery($request)->latest()->paginate(15)->withQueryString();

        return view('scm.batches.index', [
            'batches' => $batches,
            'statusLabels' => ProductionBatch::statusLabels(),
            'selectedStatus' => $request->query('status'),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole(Role::PRODUKSI, Role::GUDANG, Role::ADMIN), 403);

        $batches = $this->filteredQuery($request)->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Batch Produksi');

        $header = ['Nomor Batch', 'Dari Pengajuan', 'Dibuat Oleh', 'Status', 'Tanggal'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $statusLabels = ProductionBatch::statusLabels();
        $row = 2;
        foreach ($batches as $batch) {
            $sheet->fromArray([
                $batch->batch_number,
                $batch->materialRequest?->request_number ?? '-',
                $batch->producedBy?->name ?? '-',
                $statusLabels[$batch->status] ?? $batch->status,
                $batch->created_at->format('d/m/Y'),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'batch-produksi-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        abort_unless($request->user()->hasRole(Role::PRODUKSI, Role::GUDANG, Role::ADMIN), 403);

        $batches = $this->filteredQuery($request)->latest()->get();

        $pdf = Pdf::loadView('scm.batches.export-pdf', [
            'batches' => $batches,
            'statusLabels' => ProductionBatch::statusLabels(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('batch-produksi-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Query dasar yang dipakai bersama oleh index() dan kedua export — supaya
     * hasil export selalu konsisten dengan scope akses & filter yang aktif.
     */
    private function filteredQuery(Request $request): Builder
    {
        $user = $request->user();

        $query = ProductionBatch::with(['materialRequest', 'producedBy', 'items.labels']);

        // Gudang butuh lihat SEMUA batch yang sudah approved (utk cetak
        // label/kirim), bukan cuma miliknya sendiri — beda dengan Produksi
        // yang cuma lihat batch buatannya sendiri.
        if ($user->hasRole(Role::PRODUKSI) && ! $user->hasRole(Role::ADMIN, Role::GUDANG)) {
            $query->where('produced_by', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $query;
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::PRODUKSI), 403);

        $query = MaterialRequest::where('status', MaterialRequest::STATUS_APPROVED);

        if (! $user->hasRole(Role::ADMIN)) {
            $query->where('requested_by', $user->id);
        }

        return view('scm.batches.create', [
            'materialRequests' => $query->latest()->get(),
        ]);
    }

    public function store(StoreProductionBatchRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::PRODUKSI), 403);

        $validated = $request->validated();

        $materialRequest = MaterialRequest::where('status', MaterialRequest::STATUS_APPROVED)
            ->findOrFail($validated['material_request_id']);

        abort_unless(
            $user->hasRole(Role::ADMIN) || $materialRequest->requested_by === $user->id,
            403
        );

        $batch = DB::transaction(function () use ($validated, $user, $materialRequest) {
            $batch = ProductionBatch::create([
                'batch_number' => ProductionBatch::generateBatchNumber(),
                'material_request_id' => $materialRequest->id,
                'produced_by' => $user->id,
                'produced_at' => now(),
                'status' => ProductionBatch::STATUS_SUBMITTED,
            ]);

            foreach ($validated['items'] as $item) {
                $batch->items()->create($item);
            }

            $batch->generateApprovalSteps();

            return $batch;
        });

        return redirect()
            ->route('scm.batches.show', $batch)
            ->with('success', "Batch {$batch->batch_number} berhasil dibuat.");
    }

    public function show(Request $request, ProductionBatch $productionBatch): View
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole(Role::ADMIN, Role::GUDANG) || $productionBatch->produced_by === $user->id,
            403
        );

        $productionBatch->load(['materialRequest', 'producedBy', 'approvals.approver', 'items.labels']);

        return view('scm.batches.show', [
            'productionBatch' => $productionBatch,
        ]);
    }

    public function approve(Request $request, ProductionBatch $productionBatch): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $productionBatch->approveCurrentStepBy($request->user(), $validated['note'] ?? null)) {
            return back()->withErrors(['approval' => 'Batch ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Batch berhasil disetujui.');
    }

    public function reject(Request $request, ProductionBatch $productionBatch): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if (! $productionBatch->rejectCurrentStepBy($request->user(), $validated['note'])) {
            return back()->withErrors(['approval' => 'Batch ini sudah diproses atau bukan giliran Anda.']);
        }

        return back()->with('success', 'Batch berhasil ditolak.');
    }
}
