<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreWorkLogRequest;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\GA\WorkLog;
use App\Models\GA\WorkLogAttachment;
use App\Models\UserPagePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WorkLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = WorkLog::with(['branch', 'branchLocation', 'attachments']);

        if ($userBranch = $request->user()->branch) {
            $query->where('branch_id', $userBranch->id);
        }

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('technician_in_charge', 'like', "%{$search}%")
                    ->orWhere('technician_assist', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('work_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('work_date', '<=', $dateTo);
        }

        // Diambil sebelum paginate() supaya diagram mengikuti filter yang
        // sama dengan tabel di bawahnya, tapi tidak ikut ke-paginate.
        $technicianCounts = (clone $query)
            ->selectRaw('technician_in_charge, count(*) as total')
            ->groupBy('technician_in_charge')
            ->pluck('total', 'technician_in_charge');

        $technicianLabels = collect(WorkLog::technicianOptions())->mapWithKeys(fn ($t) => [$t => $t]);
        $technicianByCount = $technicianLabels->keys()->mapWithKeys(fn ($t) => [$t => (int) ($technicianCounts[$t] ?? 0)]);

        $workLogs = $query->latest('work_date')->latest('start_time')->paginate(15)->withQueryString();

        return $this->viewFor('worklogs.index', [
            'workLogs' => $workLogs,
            'branches' => Branch::orderedOutlets(Branch::WORK_LOG_OUTLETS),
            'categoryLabels' => WorkLog::categoryLabels(),
            'selectedBranch' => $request->query('branch_id'),
            'selectedCategory' => $request->query('category'),
            'search' => $request->query('search'),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
            'technicianLabels' => $technicianLabels,
            'technicianByCount' => $technicianByCount,
            'technicianTotal' => $technicianByCount->sum(),
        ]);
    }

    public function create(): View
    {
        return view('ga.worklogs.create', [
            'branches' => Branch::orderedOutlets(Branch::WORK_LOG_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
            'categoryLabels' => WorkLog::categoryLabels(),
            'technicianOptions' => WorkLog::technicianOptions(),
        ]);
    }

    public function store(StoreWorkLogRequest $request): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_WORKLOGS), 403);

        $validated = $request->validated();

        $workLog = new WorkLog($validated);
        $workLog->created_by = $request->user()->id;

        if ($userBranch = $request->user()->branch) {
            $workLog->branch_id = $userBranch->id;
        }

        $workLog->save();

        $this->storeAttachments($request, $workLog);

        return redirect()
            ->route('ga.worklogs.show', $workLog)
            ->with('success', 'Work Log berhasil ditambahkan.');
    }

    public function show(Request $request, WorkLog $worklog): View
    {
        if ($branch = $request->user()->branch) {
            abort_unless($worklog->branch_id === $branch->id, 404);
        }

        $worklog->load(['branch', 'branchLocation', 'createdBy', 'attachments']);

        return $this->viewFor('worklogs.show', [
            'workLog' => $worklog,
            'categoryLabels' => WorkLog::categoryLabels(),
        ]);
    }

    public function edit(WorkLog $worklog): View
    {
        $worklog->load('attachments');

        return view('ga.worklogs.edit', [
            'workLog' => $worklog,
            'branches' => Branch::orderedOutlets(Branch::WORK_LOG_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
            'categoryLabels' => WorkLog::categoryLabels(),
            'technicianOptions' => WorkLog::technicianOptions(),
        ]);
    }

    public function update(StoreWorkLogRequest $request, WorkLog $worklog): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_WORKLOGS), 403);

        $validated = $request->validated();

        $worklog->fill($validated);
        $worklog->save();

        // Foto baru MENAMBAH lampiran yang sudah ada (bukan mengganti) —
        // hapus satuan lewat destroyAttachment(), bukan lewat form edit ini.
        $this->storeAttachments($request, $worklog);

        return redirect()
            ->route('ga.worklogs.show', $worklog)
            ->with('success', 'Work Log berhasil diperbarui.');
    }

    public function destroy(Request $request, WorkLog $worklog): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_WORKLOGS), 403);

        foreach ($worklog->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->photo_path);
        }

        $worklog->delete();

        return redirect()
            ->route('ga.worklogs.index')
            ->with('success', 'Work Log berhasil dihapus.');
    }

    public function destroyAttachment(Request $request, WorkLog $worklog, WorkLogAttachment $attachment): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_WORKLOGS), 403);
        abort_unless($attachment->work_log_id === $worklog->id, 404);

        Storage::disk('public')->delete($attachment->photo_path);
        $attachment->delete();

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }

    private function storeAttachments(Request $request, WorkLog $workLog): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $photo) {
            $workLog->attachments()->create([
                'photo_path' => $photo->store('work-logs/attachments', 'public'),
            ]);
        }
    }
}
