<?php

namespace App\Http\Controllers\Outlet;

use App\Http\Controllers\Controller;
use App\Models\GA\GaRequest;
use App\Models\GA\MaintenanceJob;
use App\Models\GA\UniformStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function dashboard(Request $request): View
    {
        // outlet.branch middleware sudah menjamin user->branch ada di
        // titik ini — lihat EnsureOutletHasBranch.
        $branch = $request->user()->scopingBranch();

        return view('outlet.dashboard', [
            'branch' => $branch,
            'draftRequestCount' => GaRequest::where('branch_id', $branch->id)
                ->where('requested_by', $request->user()->id)
                ->where('status', GaRequest::STATUS_DRAFT)
                ->count(),
            'lowStockCount' => UniformStock::where('branch_id', $branch->id)->lowStock()->count(),
            'upcomingMaintenanceCount' => MaintenanceJob::where('branch_id', $branch->id)
                ->whereIn('status', [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS])
                ->where('scheduled_date', '>=', now()->toDateString())
                ->count(),
        ]);
    }
}
