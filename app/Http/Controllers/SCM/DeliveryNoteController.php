<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Http\Requests\SCM\SendDeliveryNoteRequest;
use App\Http\Requests\SCM\StoreDeliveryNoteRequest;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SCM\DeliveryNote;
use App\Models\SCM\StockBalance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Surat Jalan — dibuat Gudang (draft), wajib foto sebelum status jadi
 * "sent" (lihat send()). Outlet tujuan hanya bisa lihat/proses surat jalan
 * yang to_branch_id-nya sama dengan branch_id miliknya (query-level, bukan
 * cuma UI — lihat scopeToUserBranch()).
 */
class DeliveryNoteController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::OUTLET, Role::ADMIN), 403);

        $query = DeliveryNote::with(['fromBranch', 'toBranch', 'sentBy']);
        $this->scopeToUserBranch($query, $user);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $deliveryNotes = $query->latest()->paginate(15)->withQueryString();

        return view('scm.deliveries.index', [
            'deliveryNotes' => $deliveryNotes,
            'statusLabels' => DeliveryNote::statusLabels(),
            'selectedStatus' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::ADMIN), 403);

        // Step 2 (pilih item) di-render dari data JSON via fetch ke
        // availableLabels() — supaya selalu FEFO & sesuai stok real-time di
        // branch yang baru dipilih user di Step 1 (penting utk Admin, yang
        // from_branch_id-nya baru diketahui SETELAH halaman ini dimuat).
        // Di sini cukup cek ada/tidaknya stok SAMA SEKALI, utk empty-state.
        $hasAnyStock = StockBalance::where('qty_on_hand', '>', 0)->exists();

        return view('scm.deliveries.create', [
            'hasAnyStock' => $hasAnyStock,
            'branches' => Branch::orderedOutlets(),
            'defaultFromBranchId' => $user->branch_id,
            'isAdmin' => $user->hasRole(Role::ADMIN),
        ]);
    }

    /**
     * FEFO: daftar batch label yang tersedia (qty_on_hand > 0) di branch
     * yang sedang dipilih (from_branch_id), diurutkan ascending berdasarkan
     * expiry_date (paling dekat kedaluwarsa duluan). Dipakai wizard "Buat
     * Surat Jalan" (fetch dari Alpine) — lihat scm/deliveries/create.blade.php.
     */
    public function availableLabels(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::ADMIN), 403);

        $branchId = $user->hasRole(Role::ADMIN)
            ? $request->query('from_branch_id')
            : $user->branch_id;

        if (! $branchId) {
            return response()->json(['labels' => []]);
        }

        // Surat Jalan cuma pernah mengirim unit hasil produksi (BatchLabel) —
        // stok dari Purchasing (GoodsReceiptItem, lihat Fase 2) sengaja
        // dikecualikan di sini, itu bukan yang dikirim lewat alur ini.
        $balances = StockBalance::with('stockable.productionBatchItem.productionBatch')
            ->where('branch_id', $branchId)
            ->where('stockable_type', \App\Models\SCM\BatchLabel::class)
            ->where('qty_on_hand', '>', 0)
            ->get()
            ->sortBy(fn (StockBalance $balance) => $balance->stockable->expiry_date ?? \Carbon\Carbon::maxValue())
            ->values();

        $labels = $balances->map(function (StockBalance $balance) {
            $label = $balance->stockable;
            $item = $label->productionBatchItem;

            return [
                'id' => $label->id,
                'label_code' => $label->label_code,
                'item_name' => $item->item_name,
                'unit' => $item->unit,
                'batch_number' => $item->productionBatch->batch_number,
                'qty_available' => $balance->qty_on_hand,
                'expiry_date' => $label->expiry_date?->format('d M Y'),
                'days_until_expiry' => $label->daysUntilExpiry(),
                'is_near_expiry' => $label->isNearExpiry(),
            ];
        });

        return response()->json(['labels' => $labels]);
    }

    public function store(StoreDeliveryNoteRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::ADMIN), 403);

        $validated = $request->validated();

        $fromBranchId = $user->hasRole(Role::ADMIN)
            ? ($validated['from_branch_id'] ?? null)
            : $user->branch_id;

        abort_if(! $fromBranchId, 422, 'Outlet/gudang asal wajib ada — hubungi Admin kalau akun Anda belum terikat ke satu branch.');

        // Cegah qty_sent melebihi stok riil di gudang asal — penting sekarang
        // stok dilacak real-time (Fase 1); tanpa ini stock_balances bisa
        // minus kalau dua user pilih batch yang sama nyaris bersamaan.
        foreach ($validated['items'] as $item) {
            $available = StockBalance::where('branch_id', $fromBranchId)
                ->where('stockable_type', \App\Models\SCM\BatchLabel::class)
                ->where('stockable_id', $item['batch_label_id'])
                ->value('qty_on_hand') ?? 0;

            if ($item['qty_sent'] > $available) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Stok tidak cukup untuk salah satu item yang dipilih (sisa stok: {$available}).",
                ]);
            }
        }

        $deliveryNote = DB::transaction(function () use ($validated, $fromBranchId) {
            $deliveryNote = DeliveryNote::create([
                'delivery_code' => DeliveryNote::generateDeliveryCode(),
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $validated['to_branch_id'],
                'status' => DeliveryNote::STATUS_DRAFT,
            ]);

            foreach ($validated['items'] as $item) {
                $deliveryNote->items()->create($item);
            }

            return $deliveryNote;
        });

        // Alur wizard mobile "Buat Surat Jalan" menyatukan buat+kirim jadi
        // satu langkah terakhir ("Kirim Surat Jalan") — kalau foto sudah
        // disertakan di sini, langsung kirim juga, tidak perlu ke halaman
        // detail dulu utk klik "Kirim" terpisah.
        if ($request->hasFile('sent_photo')) {
            $this->markAsSent($deliveryNote, $request->user(), $request->file('sent_photo'));

            return redirect()
                ->route('scm.deliveries.show', $deliveryNote)
                ->with('success', "Surat Jalan {$deliveryNote->delivery_code} berhasil dikirim.");
        }

        return redirect()
            ->route('scm.deliveries.show', $deliveryNote)
            ->with('success', "Surat Jalan {$deliveryNote->delivery_code} berhasil dibuat sebagai draft.");
    }

    public function show(Request $request, DeliveryNote $deliveryNote): View
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::OUTLET, Role::ADMIN), 403);
        $this->authorizeBranchAccess($deliveryNote, $user);

        $deliveryNote->load([
            'fromBranch', 'toBranch', 'sentBy',
            'items.batchLabel.productionBatchItem.productionBatch',
            'items.discrepancy',
            'receipt.receivedBy',
        ]);

        return view('scm.deliveries.show', [
            'deliveryNote' => $deliveryNote,
        ]);
    }

    /**
     * Konfirmasi kirim — foto WAJIB (lihat SendDeliveryNoteRequest), generate
     * QR dari delivery_code setelah status jadi "sent".
     */
    public function send(SendDeliveryNoteRequest $request, DeliveryNote $deliveryNote): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::ADMIN), 403);
        $this->authorizeBranchAccess($deliveryNote, $user);
        abort_unless($deliveryNote->status === DeliveryNote::STATUS_DRAFT, 422, 'Surat jalan ini sudah dikirim sebelumnya.');

        $this->markAsSent($deliveryNote, $user, $request->file('sent_photo'));

        return back()->with('success', 'Surat jalan berhasil dikirim.');
    }

    /**
     * Dipakai baik oleh store() (alur wizard mobile: buat+kirim sekaligus)
     * maupun send() (alur lama: draft dulu, kirim belakangan) — supaya logic
     * "apa artinya kirim" (foto, QR, status) cuma ada di satu tempat.
     */
    private function markAsSent(DeliveryNote $deliveryNote, User $user, UploadedFile $photo): void
    {
        $photoPath = $photo->store('scm/delivery-notes/sent', 'public');

        $deliveryNote->update([
            'sent_by' => $user->id,
            'sent_photo_path' => $photoPath,
            'sent_at' => now(),
            'status' => DeliveryNote::STATUS_SENT,
            'qr_code' => $this->qrSvgFor($deliveryNote->delivery_code),
        ]);
    }

    /**
     * Surat Jalan (PDF).
     */
    public function document(Request $request, DeliveryNote $deliveryNote): Response
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::OUTLET, Role::ADMIN), 403);
        $this->authorizeBranchAccess($deliveryNote, $user);

        $deliveryNote->load(['fromBranch', 'toBranch', 'sentBy', 'items.batchLabel.productionBatchItem']);

        $pdf = Pdf::loadView('scm.deliveries.document-pdf', [
            'deliveryNote' => $deliveryNote,
        ])->setPaper('a4', 'portrait');

        $filename = 'SJ-'.str_replace('/', '-', $deliveryNote->delivery_code).'.pdf';

        return $pdf->stream($filename);
    }

    private function qrSvgFor(string $deliveryCode): string
    {
        $result = (new Builder(writer: new SvgWriter()))->build(
            data: $deliveryCode,
            size: 200,
            margin: 6,
        );

        return $result->getString();
    }

    /**
     * Outlet hanya boleh akses surat jalan yang ditujukan ke branch-nya
     * sendiri; Gudang hanya yang berasal dari branch-nya sendiri. Admin
     * bebas. Dicek di level query (index) & langsung di sini (show/send/
     * document) — bukan cuma disembunyikan di UI.
     */
    private function authorizeBranchAccess(DeliveryNote $deliveryNote, User $user): void
    {
        if ($user->hasRole(Role::ADMIN)) {
            return;
        }

        if ($user->hasRole(Role::OUTLET)) {
            abort_unless($deliveryNote->to_branch_id === $user->branch_id, 403);
        }

        if ($user->hasRole(Role::GUDANG)) {
            abort_unless($deliveryNote->from_branch_id === $user->branch_id, 403);
        }
    }

    private function scopeToUserBranch(EloquentBuilder $query, User $user): void
    {
        if ($user->hasRole(Role::ADMIN)) {
            return;
        }

        if ($user->hasRole(Role::OUTLET)) {
            $query->where('to_branch_id', $user->branch_id);
        }

        if ($user->hasRole(Role::GUDANG)) {
            $query->where('from_branch_id', $user->branch_id);
        }
    }
}
