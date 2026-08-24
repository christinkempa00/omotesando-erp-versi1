<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Models\GA\Asset;
use App\Models\GA\GaRequest;
use App\Models\GA\MaintenanceJob;
use App\Models\GA\UniformStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard Head: statistik/grafik lintas modul GA (read-only monitoring).
 * Alur approval GaRequest (Finance->Head->Finance) dihapus total 24/08/2026
 * — Head sekarang murni memantau, tidak ada lagi approve/reject di sini.
 */
class HeadController extends Controller
{
    /**
     * Dashboard — HALAMAN OVERVIEW MURNI: statistik/grafik per modul saja,
     * daftar pengajuan sendiri ada di "Semua Pengajuan". Dipisah supaya
     * tiap halaman Head punya satu fungsi yang jelas & tidak tumpang tindih.
     */
    public function dashboard(): View
    {
        // Ringkasan lintas modul GA — dipakai sebagai caption di tiap kartu chart.
        $moduleSummary = [
            'requests' => [
                'draft' => GaRequest::where('status', GaRequest::STATUS_DRAFT)->count(),
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
        //     ATAS SELURUH data modul, dipakai dashboard supaya lebih mudah
        //     dibaca lewat grafik ketimbang angka.
        $requestByStatus = GaRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $requestDonut = [
            'draft' => (int) ($requestByStatus[GaRequest::STATUS_DRAFT] ?? 0),
            'submitted' => (int) ($requestByStatus[GaRequest::STATUS_SUBMITTED] ?? 0),
        ];

        $assetByStatus = Asset::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $uniformByStatus = UniformStock::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $maintenanceByStatus = MaintenanceJob::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $moduleCharts = [
            'requests' => [
                'data' => $requestDonut,
                'labels' => ['draft' => 'Draft', 'submitted' => 'Diajukan'],
                'colors' => ['draft' => '#9ca3af', 'submitted' => '#22c55e'],
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
                'colors' => ['bagus' => '#22c55e', 'rusak' => '#ef4444'],
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

        $query = GaRequest::with(['branch', 'requestedBy'])->latest();

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
        $gaRequest->load(['items', 'branch', 'division', 'requestedBy']);

        return view('head.requests.show', [
            'gaRequest' => $gaRequest,
            'categoryLabels' => GaRequest::categoryLabels(),
        ]);
    }
}
