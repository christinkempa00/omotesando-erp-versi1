<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Models\GA\MaintenanceJob;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring jadwal pemeliharaan untuk Head — read-only (reuse model
 * MaintenanceJob & query filter yang sama dengan MaintenanceJobController),
 * tanpa aksi jadwalkan/edit/tandai selesai.
 */
class HeadMaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaintenanceJob::with(['asset', 'branch'])->latest('scheduled_date');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('job_code', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('pic_name', 'like', "%{$search}%");
            });
        }

        $jobs = $query->paginate(15)->withQueryString();

        $summary = [
            'scheduled' => MaintenanceJob::where('status', MaintenanceJob::STATUS_SCHEDULED)->count(),
            'in_progress' => MaintenanceJob::where('status', MaintenanceJob::STATUS_IN_PROGRESS)->count(),
            'completed' => MaintenanceJob::where('status', MaintenanceJob::STATUS_COMPLETED)->count(),
            'overdue' => MaintenanceJob::whereIn('status', [
                MaintenanceJob::STATUS_SCHEDULED,
                MaintenanceJob::STATUS_IN_PROGRESS,
            ])->whereDate('scheduled_date', '<', now()->toDateString())->count(),
        ];

        return view('head.maintenance.index', [
            'jobs' => $jobs,
            'summary' => $summary,
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'selectedStatus' => $status,
            'selectedType' => $type,
            'search' => $search,
        ]);
    }
}
