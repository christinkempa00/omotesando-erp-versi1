<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Concerns\ResolvesApprovalSignature;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\GA\GaRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Approval Inbox — daftar SEMUA item yang menunggu approval Head, lintas
 * modul (approvable_type apapun), dalam satu halaman. Dibangun di atas tabel
 * approvals & trait Approvable yang generik (lihat app/Models/Concerns/Approvable.php)
 * — TIDAK ada logic approval khusus per modul di sini. Saat ini baru
 * GaRequest yang memakai trait itu, tapi controller ini tidak berasumsi
 * demikian: begitu model lain (mis. PurchaseOrder) mengadopsi Approvable,
 * item-nya otomatis muncul di sini tanpa perubahan kode.
 *
 * Tanda tangan wajib (lihat ResolvesApprovalSignature) SENGAJA cuma
 * ditegakkan utk GaRequest — modul lain yang nanti ikut memakai
 * Approvable belum masuk pilot ini, jadi approve-nya tetap tanpa tanda
 * tangan sampai memang diperluas.
 */
class HeadApprovalController extends Controller
{
    use ResolvesApprovalSignature;

    public function index(Request $request): View
    {
        $user = $request->user();
        $module = $request->query('module');
        $sort = $request->query('sort', 'oldest');
        $search = $request->query('search');
        $category = $request->query('category');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Approval::with('approvable')
            ->where('approver_role', Role::HEAD)
            ->where('status', Approval::STATUS_PENDING);

        if ($module) {
            $query->where('approvable_type', $module);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($category) {
            $query->whereHasMorph('approvable', [GaRequest::class], function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $query->orderBy('created_at', $sort === 'newest' ? 'desc' : 'asc');

        // Ambil semua kandidat dulu (biasanya kecil jumlahnya utk approval
        // queue), lalu filter yang BENAR giliran sekarang — step sebelumnya
        // di model yang sama sudah approved. Query DB saja tidak cukup utk
        // ini karena "giliran" ditentukan lintas baris per approvable.
        $pending = $query->get()->filter(function (Approval $approval) {
            $approvable = $approval->approvable;

            if (! $approvable) {
                return false;
            }

            $current = $approvable->currentApprovalStep();

            return $current && $current->is($approval);
        })->values();

        // Pencarian dilakukan di memori (bukan query DB) karena approvable_type
        // bisa macam-macam model — field yang dicari (nomor/nama/deskripsi)
        // diambil dengan aman lewat null-coalescing, jadi tidak error kalau
        // model lain yang mengadopsi Approvable nanti tidak punya field itu.
        if ($search) {
            $pending = $pending->filter(function (Approval $approval) use ($search) {
                $approvable = $approval->approvable;
                $haystack = collect([
                    $approvable->request_number ?? null,
                    $approvable->po_number ?? null,
                    $approvable->requester_name ?? null,
                    $approvable->description ?? null,
                ])->filter()->join(' ');

                return stripos($haystack, $search) !== false;
            })->values();
        }

        $page = (int) $request->query('page', 1);
        $perPage = 15;
        $items = new LengthAwarePaginator(
            $pending->forPage($page, $perPage)->values(),
            $pending->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'page']
        );

        // Riwayat approval yang sudah diputuskan oleh Head yang sedang login —
        // dipindah ke sini (dari Dashboard lama) supaya satu halaman ini jadi
        // tempat tunggal utk semua urusan approval: yang menunggu & riwayatnya.
        $historyQuery = Approval::with('approvable')
            ->where('approver_role', Role::HEAD)
            ->where('approver_user_id', $user->id)
            ->whereIn('status', [Approval::STATUS_APPROVED, Approval::STATUS_REJECTED]);

        if ($module) {
            $historyQuery->where('approvable_type', $module);
        }
        if ($dateFrom) {
            $historyQuery->whereDate('approved_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $historyQuery->whereDate('approved_at', '<=', $dateTo);
        }
        if ($category) {
            $historyQuery->whereHasMorph('approvable', [GaRequest::class], function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $history = $historyQuery->latest('approved_at')->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('head.approvals.index', [
            'items' => $items,
            'totalPending' => $pending->count(),
            'history' => $history,
            'moduleLabels' => Approval::approvableModuleLabels(),
            'categoryLabels' => GaRequest::categoryLabels(),
            'selectedModule' => $module,
            'selectedCategory' => $category,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sort' => $sort,
            'search' => $search,
        ]);
    }

    public function approve(Request $request, Approval $approval): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'signature_data' => ['nullable', 'string'],
            'signature_use_saved' => ['nullable', 'boolean'],
        ]);

        $approvable = $approval->approvable;

        // Tanda tangan cuma wajib utk GaRequest (lihat docblock kelas ini) —
        // modul lain yang belum masuk pilot tetap approve tanpa tanda tangan.
        $signaturePath = $approvable instanceof GaRequest
            ? $this->resolveMandatorySignature($request, $validated)
            : null;

        if (! $approvable || ! $approvable->approveCurrentStepBy($request->user(), $validated['note'] ?? null, $signaturePath)) {
            return back()->withErrors(['approval' => 'Item ini bukan giliran Anda untuk approve, atau sudah diproses pihak lain.']);
        }

        return back()->with('success', 'Item berhasil disetujui.');
    }

    /**
     * Reject sengaja TIDAK butuh tanda tangan (cuma approve yang wajib) —
     * lihat ResolvesApprovalSignature.
     */
    public function reject(Request $request, Approval $approval): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $approvable = $approval->approvable;

        if (! $approvable || ! $approvable->rejectCurrentStepBy($request->user(), $validated['note'])) {
            return back()->withErrors(['approval' => 'Item ini bukan giliran Anda untuk reject, atau sudah diproses pihak lain.']);
        }

        return back()->with('success', 'Item berhasil ditolak.');
    }
}
