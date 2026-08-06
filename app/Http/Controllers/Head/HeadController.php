<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Concerns\ResolvesApprovalSignature;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\GA\Asset;
use App\Models\GA\GaRequest;
use App\Models\GA\MaintenanceJob;
use App\Models\GA\UniformStock;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard Head: menampilkan & memproses approval berjenjang yang SUDAH ADA
 * (tabel approvals + trait Approvable pada GaRequest). Controller ini tidak
 * membuat ulang logic approval — hanya memanggil
 * GaRequest::approveCurrentStepBy()/rejectCurrentStepBy() yang didefinisikan
 * di app/Models/Concerns/Approvable.php.
 */
class HeadController extends Controller
{
    use ResolvesApprovalSignature;

    /**
     * Dashboard — HALAMAN OVERVIEW MURNI: statistik/grafik per modul saja,
     * tanpa tabel approval (itu ada di Approval Inbox) atau daftar pengajuan
     * (itu ada di Semua Pengajuan). Dipisah supaya tiap halaman Head punya
     * satu fungsi yang jelas & tidak tumpang tindih.
     */
    public function dashboard(): View
    {
        $pendingForHead = GaRequest::whereHas('approvals', function ($q) {
            $q->where('approver_role', Role::HEAD)->where('status', Approval::STATUS_PENDING);
        })->get()->filter(function (GaRequest $r) {
            $current = $r->currentApprovalStep();

            return $current && $current->approver_role === Role::HEAD;
        })->count();

        // Ringkasan lintas modul GA — dipakai sebagai caption di tiap kartu chart.
        $moduleSummary = [
            'requests' => [
                'pending' => $pendingForHead,
                'total' => GaRequest::count(),
            ],
            'assets' => [
                'total' => Asset::count(),
                'bagus' => Asset::where('status', Asset::STATUS_BAGUS)->count(),
            ],
            'uniforms' => [
                'total_stock' => (int) UniformStock::sum('available_stock'),
                'low_stock' => UniformStock::lowStock()->count(),
            ],
            'maintenance' => [
                'active' => MaintenanceJob::whereIn('status', [
                    MaintenanceJob::STATUS_SCHEDULED,
                    MaintenanceJob::STATUS_IN_PROGRESS,
                ])->count(),
                'completed' => MaintenanceJob::where('status', MaintenanceJob::STATUS_COMPLETED)->count(),
            ],
        ];

        // --- Statistik per modul (donut chart) — data breakdown status
        //     ATAS SELURUH data modul (bukan cuma yang giliran Head), dipakai
        //     dashboard supaya lebih mudah dibaca lewat grafik ketimbang angka.
        $requestByStatus = GaRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $requestDonut = [
            'pending' => (int) ($requestByStatus[GaRequest::STATUS_DRAFT] ?? 0)
                + (int) ($requestByStatus[GaRequest::STATUS_SUBMITTED] ?? 0)
                + (int) ($requestByStatus[GaRequest::STATUS_IN_REVIEW] ?? 0),
            'approved' => (int) ($requestByStatus[GaRequest::STATUS_APPROVED] ?? 0)
                + (int) ($requestByStatus[GaRequest::STATUS_RECEIVED] ?? 0),
            'rejected' => (int) ($requestByStatus[GaRequest::STATUS_REJECTED] ?? 0),
        ];

        $assetByStatus = Asset::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $uniformByStatus = UniformStock::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $maintenanceByStatus = MaintenanceJob::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $moduleCharts = [
            'requests' => [
                'data' => $requestDonut,
                'labels' => ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'],
                'colors' => ['pending' => '#3b82f6', 'approved' => '#22c55e', 'rejected' => '#ef4444'],
                'total' => array_sum($requestDonut),
            ],
            'assets' => [
                'data' => $assetByStatus,
                'labels' => Asset::statusLabels(),
                'colors' => ['bagus' => '#22c55e', 'dalam_pemeliharaan' => '#eab308', 'rusak' => '#ef4444'],
                'total' => Asset::count(),
            ],
            'uniforms' => [
                'data' => $uniformByStatus,
                'labels' => UniformStock::statusLabels(),
                'colors' => ['bagus' => '#22c55e', 'dalam_pemeliharaan' => '#eab308', 'rusak' => '#ef4444'],
                'total' => UniformStock::count(),
            ],
            'maintenance' => [
                'data' => $maintenanceByStatus,
                'labels' => MaintenanceJob::statusLabels(),
                'colors' => ['scheduled' => '#3b82f6', 'in_progress' => '#eab308', 'completed' => '#22c55e', 'cancelled' => '#9ca3af'],
                'total' => MaintenanceJob::count(),
            ],
        ];

        // --- Biaya pemeliharaan per bulan (6 bulan terakhir termasuk bulan
        //     ini) — dipakai bar chart di dashboard. Dikelompokkan lewat
        //     scheduled_date (selalu terisi), bukan completed_at (nullable
        //     sampai pekerjaan selesai).
        $maintenanceCostByMonth = collect(range(5, 0))->map(function (int $monthsAgo) {
            // subMonthsNoOverflow, bukan subMonths — supaya tanggal akhir
            // bulan (mis. 29/30/31) tidak "meluap" ke bulan berikutnya saat
            // bulan tujuan lebih pendek (kasus nyata: 29 Jul - 5 bulan harus
            // jadi 28 Feb, bukan overflow ke 1 Mar).
            $month = now()->subMonthsNoOverflow($monthsAgo);

            return [
                'label' => $month->translatedFormat('M Y'),
                'value' => (float) MaintenanceJob::whereYear('scheduled_date', $month->year)
                    ->whereMonth('scheduled_date', $month->month)
                    ->sum('cost'),
            ];
        })->values()->all();

        $totalMaintenanceCost = (float) MaintenanceJob::sum('cost');
        $totalRequestCost = (float) GaRequest::sum('total_amount');

        return view('head.dashboard', [
            'moduleSummary' => $moduleSummary,
            'moduleCharts' => $moduleCharts,
            'maintenanceCostByMonth' => $maintenanceCostByMonth,
            'totalMaintenanceCost' => $totalMaintenanceCost,
            'totalRequestCost' => $totalRequestCost,
        ]);
    }

    /**
     * Semua Pengajuan — daftar SEMUA GaRequest (bukan cuma yang lagi giliran
     * Head), supaya Head bisa memantau progres tiap pengajuan sejak dibuat
     * (mis. masih menunggu Finance step 1) sampai akhirnya giliran Head utk
     * approve/reject. Halaman detail (head.requests.show) sudah menampilkan
     * "Alur Persetujuan" lengkap & tombol approve/reject otomatis hanya
     * muncul kalau memang giliran Head — daftar ini murni titik masuk utk
     * menemukan & memantau pengajuan apa pun, bukan mengubah logic approval.
     */
    public function requestsIndex(Request $request): View
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');

        $query = GaRequest::with(['branch', 'requestedBy', 'approvals'])->latest();

        if ($category) {
            $query->where('category', $category);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('head.requests.index', [
            'requests' => $requests,
            'categoryLabels' => GaRequest::categoryLabels(),
            'statusLabels' => GaRequest::statusLabels(),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'search' => $search,
        ]);
    }

    /**
     * Detail pengajuan untuk Head — reuse model GaRequest & relasinya langsung
     * (bukan lewat GaRequestController/ga.requests.show, karena Head sudah
     * tidak diberi akses ke grup route ga.).
     */
    public function showRequest(GaRequest $gaRequest): View
    {
        $gaRequest->load(['items', 'branch', 'division', 'requestedBy', 'approvals.approver']);

        return view('head.requests.show', [
            'gaRequest' => $gaRequest,
            'categoryLabels' => GaRequest::categoryLabels(),
        ]);
    }

    public function approve(Request $request, GaRequest $gaRequest): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'signature_data' => ['nullable', 'string'],
            'signature_use_saved' => ['nullable', 'boolean'],
        ]);

        $signaturePath = $this->resolveMandatorySignature($request, $validated);

        if (! $gaRequest->approveCurrentStepBy($request->user(), $validated['note'] ?? null, $signaturePath)) {
            return back()->withErrors(['approval' => 'Pengajuan ini bukan giliran Anda untuk approve, atau sudah diproses pihak lain.']);
        }

        return back()->with('success', "Pengajuan {$gaRequest->request_number} berhasil disetujui.");
    }

    /**
     * Reject sengaja TIDAK butuh tanda tangan (cuma approve yang wajib) —
     * lihat ResolvesApprovalSignature.
     */
    public function reject(Request $request, GaRequest $gaRequest): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if (! $gaRequest->rejectCurrentStepBy($request->user(), $validated['note'] ?? null)) {
            return back()->withErrors(['approval' => 'Pengajuan ini bukan giliran Anda untuk reject, atau sudah diproses pihak lain.']);
        }

        return back()->with('success', "Pengajuan {$gaRequest->request_number} ditolak.");
    }
}
