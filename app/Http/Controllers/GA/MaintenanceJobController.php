<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreMaintenanceJobRequest;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\GA\Asset;
use App\Models\GA\MaintenanceJob;
use App\Models\UserPagePermission;
use App\Services\TelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MaintenanceJobController extends Controller
{
    public function index(Request $request): View
    {
        $query = MaintenanceJob::with(['asset', 'branch', 'branchLocation'])->latest('scheduled_date');

        if ($userBranch = $request->user()->branch) {
            $query->where('branch_id', $userBranch->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($assetId = $request->query('asset_id')) {
            $query->where('asset_id', $assetId);
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

        // Ringkasan kartu status — scoped ke branch user kalau ada (Outlet dkk)
        $summaryQuery = fn () => $userBranch ? MaintenanceJob::where('branch_id', $userBranch->id) : MaintenanceJob::query();
        $summary = [
            'scheduled' => $summaryQuery()->where('status', MaintenanceJob::STATUS_SCHEDULED)->count(),
            'in_progress' => $summaryQuery()->where('status', MaintenanceJob::STATUS_IN_PROGRESS)->count(),
            'completed' => $summaryQuery()->where('status', MaintenanceJob::STATUS_COMPLETED)->count(),
            'overdue' => $summaryQuery()->whereIn('status', [
                MaintenanceJob::STATUS_SCHEDULED,
                MaintenanceJob::STATUS_IN_PROGRESS,
            ])->whereDate('scheduled_date', '<', now()->toDateString())->count(),
        ];

        // --- Kalender mini bulan berjalan (untuk navigasi, indikator hari ada
        //     jadwal, & klik tanggal utk lihat tiket jadwal hari itu) ---
        $calendarMonth = $request->filled('month')
            ? Carbon::parse($request->query('month').'-01')
            : now()->startOfMonth();

        // Rentang grid mini calendar melebar sedikit ke bulan sebelum/sesudah
        // supaya baris pertama/terakhir tetap 7 kolom penuh (lihat _mini-calendar).
        $gridStart = $calendarMonth->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $calendarJobsQuery = MaintenanceJob::with('asset')
            ->whereBetween('scheduled_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('scheduled_time');
        if ($userBranch) {
            $calendarJobsQuery->where('branch_id', $userBranch->id);
        }
        $calendarJobs = $calendarJobsQuery->get();

        $jobIdsByDate = $calendarJobs
            ->groupBy(fn (MaintenanceJob $job) => $job->scheduled_date->toDateString())
            ->map(fn ($jobs) => $jobs->pluck('id')->values());

        // --- Jadwal mendatang (list ringkas, mirip "upcoming appointment") ---
        $upcomingJobsQuery = MaintenanceJob::with('asset')
            ->whereIn('status', [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5);
        if ($userBranch) {
            $upcomingJobsQuery->where('branch_id', $userBranch->id);
        }
        $upcomingJobs = $upcomingJobsQuery->get();

        return $this->viewFor('maintenance.index', [
            'jobs' => $jobs,
            'summary' => $summary,
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'selectedStatus' => $status,
            'selectedType' => $type,
            'search' => $search,
            'calendarMonth' => $calendarMonth,
            'calendarJobs' => $calendarJobs,
            'jobIdsByDate' => $jobIdsByDate,
            'upcomingJobs' => $upcomingJobs,
        ]);
    }

    public function create(Request $request): View
    {
        return view('ga.maintenance.create', [
            'assets' => Asset::orderBy('name')->get(),
            'branches' => Branch::orderedOutlets(Branch::GA_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'priorityLabels' => MaintenanceJob::priorityLabels(),
            'selectedAssetId' => $request->query('asset_id'),
        ]);
    }

    public function store(StoreMaintenanceJobRequest $request): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_MAINTENANCE), 403);

        $validated = $request->validated();

        $job = new MaintenanceJob($validated);
        $job->job_code = MaintenanceJob::generateJobCode();
        $job->checklist = $this->normalizeChecklist($request->input('checklist', []));
        $job->created_by = $request->user()->id;

        if ($job->status === MaintenanceJob::STATUS_COMPLETED) {
            $job->completed_at = now();
        }

        $job->save();

        app(TelegramNotifier::class)->sendMessage($job->telegramText('created', $request->user()));

        return redirect()
            ->route('ga.maintenance.show', $job)
            ->with('success', "Pekerjaan {$job->job_code} berhasil dijadwalkan.");
    }

    public function show(Request $request, MaintenanceJob $maintenance): View
    {
        if ($branch = $request->user()->branch) {
            abort_unless($maintenance->branch_id === $branch->id, 404);
        }

        $maintenance->load(['asset', 'branch', 'branchLocation', 'createdBy']);

        return $this->viewFor('maintenance.show', [
            'job' => $maintenance,
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'priorityLabels' => MaintenanceJob::priorityLabels(),
        ]);
    }

    public function edit(MaintenanceJob $maintenance): View
    {
        return view('ga.maintenance.edit', [
            'job' => $maintenance,
            'assets' => Asset::orderBy('name')->get(),
            'branches' => Branch::orderedOutlets(Branch::GA_OUTLETS, $maintenance->branch?->name),
            'branchLocations' => BranchLocation::groupedByBranch(),
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'priorityLabels' => MaintenanceJob::priorityLabels(),
        ]);
    }

    public function update(StoreMaintenanceJobRequest $request, MaintenanceJob $maintenance): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_MAINTENANCE), 403);

        $validated = $request->validated();

        $maintenance->fill($validated);
        $maintenance->checklist = $this->normalizeChecklist(
            $request->input('checklist', []),
            $maintenance->checklist ?? []
        );

        // Set / bersihkan completed_at sesuai status
        if ($maintenance->status === MaintenanceJob::STATUS_COMPLETED && ! $maintenance->completed_at) {
            $maintenance->completed_at = now();
        } elseif ($maintenance->status !== MaintenanceJob::STATUS_COMPLETED) {
            $maintenance->completed_at = null;
        }

        $maintenance->save();

        app(TelegramNotifier::class)->sendMessage($maintenance->telegramText('updated', $request->user()));

        return redirect()
            ->route('ga.maintenance.show', $maintenance)
            ->with('success', "Pekerjaan {$maintenance->job_code} berhasil diperbarui.");
    }

    public function destroy(Request $request, MaintenanceJob $maintenance): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_MAINTENANCE), 403);

        app(TelegramNotifier::class)->sendMessage($maintenance->telegramText('deleted', $request->user()));

        $maintenance->delete();

        return redirect()
            ->route('ga.maintenance.index')
            ->with('success', 'Pekerjaan pemeliharaan berhasil dihapus.');
    }

    /**
     * Tandai pekerjaan selesai (dengan catatan penyelesaian opsional).
     */
    public function complete(Request $request, MaintenanceJob $maintenance): RedirectResponse
    {
        abort_unless($request->user()->canEdit(UserPagePermission::PAGE_MAINTENANCE), 403);

        $request->validate([
            'completion_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $maintenance->status = MaintenanceJob::STATUS_COMPLETED;
        $maintenance->completion_notes = $request->input('completion_notes');
        $maintenance->completed_at = now();

        // Centang semua item checklist
        $checklist = $maintenance->checklist ?? [];
        $checklist = array_map(function ($item) {
            $item['done'] = true;

            return $item;
        }, $checklist);
        $maintenance->checklist = $checklist;

        $maintenance->save();

        return redirect()
            ->route('ga.maintenance.show', $maintenance)
            ->with('success', "Pekerjaan {$maintenance->job_code} ditandai selesai.");
    }

    /**
     * Ubah array teks checklist dari form jadi struktur [{text, done}].
     * Mempertahankan status 'done' lama bila teksnya cocok.
     */
    private function normalizeChecklist(array $texts, array $existing = []): array
    {
        $doneByText = collect($existing)
            ->mapWithKeys(fn ($item) => [($item['text'] ?? '') => ($item['done'] ?? false)]);

        return collect($texts)
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '')
            ->values()
            ->map(fn ($text) => [
                'text' => $text,
                'done' => (bool) ($doneByText[$text] ?? false),
            ])
            ->all();
    }
}
