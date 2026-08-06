<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreMaintenanceJobRequest;
use App\Models\Branch;
use App\Models\GA\Asset;
use App\Models\GA\MaintenanceJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MaintenanceJobController extends Controller
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

        // Ringkasan kartu status
        $summary = [
            'scheduled' => MaintenanceJob::where('status', MaintenanceJob::STATUS_SCHEDULED)->count(),
            'in_progress' => MaintenanceJob::where('status', MaintenanceJob::STATUS_IN_PROGRESS)->count(),
            'completed' => MaintenanceJob::where('status', MaintenanceJob::STATUS_COMPLETED)->count(),
            'overdue' => MaintenanceJob::whereIn('status', [
                MaintenanceJob::STATUS_SCHEDULED,
                MaintenanceJob::STATUS_IN_PROGRESS,
            ])->whereDate('scheduled_date', '<', now()->toDateString())->count(),
        ];

        // --- Kalender minggu berjalan (section jadwal visual) ---
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->query('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $weekJobs = MaintenanceJob::with('asset')
            ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('scheduled_time')
            ->get();

        // --- Kalender mini bulan berjalan (untuk navigasi & indikator hari ada jadwal) ---
        $calendarMonth = $request->filled('month')
            ? Carbon::parse($request->query('month').'-01')
            : $weekStart->copy()->startOfMonth();

        $jobDatesInMonth = MaintenanceJob::whereBetween('scheduled_date', [
            $calendarMonth->copy()->startOfMonth()->toDateString(),
            $calendarMonth->copy()->endOfMonth()->toDateString(),
        ])->pluck('scheduled_date')->map(fn ($d) => $d->toDateString())->unique();

        // --- Jadwal mendatang (list ringkas, mirip "upcoming appointment") ---
        $upcomingJobs = MaintenanceJob::with('asset')
            ->whereIn('status', [MaintenanceJob::STATUS_SCHEDULED, MaintenanceJob::STATUS_IN_PROGRESS])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        return view('ga.maintenance.index', [
            'jobs' => $jobs,
            'summary' => $summary,
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'selectedStatus' => $status,
            'selectedType' => $type,
            'search' => $search,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekJobs' => $weekJobs,
            'calendarMonth' => $calendarMonth,
            'jobDatesInMonth' => $jobDatesInMonth,
            'upcomingJobs' => $upcomingJobs,
        ]);
    }

    public function create(Request $request): View
    {
        return view('ga.maintenance.create', [
            'assets' => Asset::orderBy('name')->get(),
            'branches' => Branch::orderedOutlets(),
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'priorityLabels' => MaintenanceJob::priorityLabels(),
            'selectedAssetId' => $request->query('asset_id'),
        ]);
    }

    public function store(StoreMaintenanceJobRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $job = new MaintenanceJob($validated);
        $job->job_code = MaintenanceJob::generateJobCode();
        $job->checklist = $this->normalizeChecklist($request->input('checklist', []));
        $job->created_by = $request->user()->id;

        if ($job->status === MaintenanceJob::STATUS_COMPLETED) {
            $job->completed_at = now();
        }

        $job->save();

        return redirect()
            ->route('ga.maintenance.show', $job)
            ->with('success', "Pekerjaan {$job->job_code} berhasil dijadwalkan.");
    }

    public function show(MaintenanceJob $maintenance): View
    {
        $maintenance->load(['asset', 'branch', 'createdBy']);

        return view('ga.maintenance.show', [
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
            'branches' => Branch::orderedOutlets(),
            'statusLabels' => MaintenanceJob::statusLabels(),
            'typeLabels' => MaintenanceJob::typeLabels(),
            'priorityLabels' => MaintenanceJob::priorityLabels(),
        ]);
    }

    public function update(StoreMaintenanceJobRequest $request, MaintenanceJob $maintenance): RedirectResponse
    {
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

        return redirect()
            ->route('ga.maintenance.show', $maintenance)
            ->with('success', "Pekerjaan {$maintenance->job_code} berhasil diperbarui.");
    }

    public function destroy(MaintenanceJob $maintenance): RedirectResponse
    {
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
