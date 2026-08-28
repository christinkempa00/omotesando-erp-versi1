<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GA\Asset;
use App\Models\GA\GaRequest;
use App\Models\GA\MaintenanceJob;
use App\Models\GA\UniformStock;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $assetTotal = Asset::count();

        $assetByStatus = collect(Asset::statusLabels())
            ->keys()
            ->mapWithKeys(fn ($status) => [$status => 0])
            ->merge(Asset::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'));

        $uniformTotal = UniformStock::count();

        $uniformByStatus = collect(UniformStock::statusLabels())
            ->keys()
            ->mapWithKeys(fn ($status) => [$status => 0])
            ->merge(UniformStock::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'));

        $maintenanceSummary = [
            'scheduled' => MaintenanceJob::where('status', MaintenanceJob::STATUS_SCHEDULED)->count(),
            'in_progress' => MaintenanceJob::where('status', MaintenanceJob::STATUS_IN_PROGRESS)->count(),
            'completed' => MaintenanceJob::where('status', MaintenanceJob::STATUS_COMPLETED)->count(),
            'overdue' => MaintenanceJob::whereIn('status', [
                MaintenanceJob::STATUS_SCHEDULED,
                MaintenanceJob::STATUS_IN_PROGRESS,
            ])->whereDate('scheduled_date', '<', now()->toDateString())->count(),
        ];

        $upcomingJobs = MaintenanceJob::with(['asset', 'branch', 'branchLocation'])
            ->whereIn('status', [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS])
            ->whereDate('scheduled_date', '<=', now()->addDays(7)->toDateString())
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        $totalMaintenanceCost = (float) MaintenanceJob::sum('cost');

        // Angka ringkas yang paling relevan buat Head lihat sekilas — bukan
        // breakdown kondisi/status (itu sudah ada di section detail di bawah).
        $gaRequestTotal = GaRequest::count();

        // --- Rincian per outlet: gabungan Aset, Seragam, & Pemeliharaan ---
        $outletBreakdown = Branch::orderedOutlets(Branch::GA_OUTLETS)->map(function (Branch $branch) {
            return [
                'name' => $branch->name,
                'total_assets' => Asset::where('branch_id', $branch->id)->count(),
                'total_uniform_stock' => (int) UniformStock::where('branch_id', $branch->id)->sum('available_stock'),
                'maintenance_scheduled' => MaintenanceJob::where('branch_id', $branch->id)
                    ->whereIn('status', [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS])
                    ->count(),
                'maintenance_completed' => MaintenanceJob::where('branch_id', $branch->id)
                    ->where('status', MaintenanceJob::STATUS_COMPLETED)
                    ->count(),
                'maintenance_cost' => (float) MaintenanceJob::where('branch_id', $branch->id)->sum('cost'),
            ];
        });

        $maintenanceHistory = MaintenanceJob::with(['asset', 'branch'])
            ->where('status', MaintenanceJob::STATUS_COMPLETED)
            ->latest('completed_at')
            ->limit(8)
            ->get();

        return view('ga.dashboard', [
            'assetTotal' => $assetTotal,
            'assetByStatus' => $assetByStatus,
            'uniformTotal' => $uniformTotal,
            'uniformByStatus' => $uniformByStatus,
            'maintenanceSummary' => $maintenanceSummary,
            'upcomingJobs' => $upcomingJobs,
            'assetStatusLabels' => Asset::statusLabels(),
            'uniformStatusLabels' => UniformStock::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'totalMaintenanceCost' => $totalMaintenanceCost,
            'gaRequestTotal' => $gaRequestTotal,
            'outletBreakdown' => $outletBreakdown,
            'maintenanceHistory' => $maintenanceHistory,
        ]);
    }
}
