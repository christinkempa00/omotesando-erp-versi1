<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Http\Requests\SCM\StoreDeliveryReceiptRequest;
use App\Models\Role;
use App\Models\SCM\DeliveryNote;
use App\Models\SCM\DiscrepancyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Terima di Outlet — foto WAJIB (lihat StoreDeliveryReceiptRequest), qty
 * diterima dikonfirmasi per item. Kalau ada selisih, DeliveryNoteItemObserver
 * otomatis bikin DiscrepancyReport per item saat qty_received disimpan;
 * status agregat DeliveryNote ditentukan di sini setelah semua item disimpan.
 */
class DeliveryReceiptController extends Controller
{
    public function store(StoreDeliveryReceiptRequest $request, DeliveryNote $deliveryNote): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::OUTLET, Role::ADMIN), 403);
        abort_unless(
            $user->hasRole(Role::ADMIN) || $deliveryNote->to_branch_id === $user->branch_id,
            403
        );
        abort_unless($deliveryNote->status === DeliveryNote::STATUS_SENT, 422, 'Surat jalan ini belum dikirim atau sudah diterima.');

        $validated = $request->validated();
        $photoPath = $request->file('received_photo')->store('scm/delivery-notes/received', 'public');

        DB::transaction(function () use ($validated, $user, $deliveryNote, $photoPath) {
            // Update qty_received tiap item DULU (trigger DeliveryNoteItemObserver
            // kalau ada selisih), baru SETELAH itu buat baris DeliveryReceipt —
            // urutan ini penting: DeliveryReceiptObserver (stok masuk ke outlet,
            // lihat Fase 1) baca qty_received dari items saat receipt dibuat,
            // jadi datanya harus sudah lengkap lebih dulu.
            foreach ($validated['items'] as $itemInput) {
                $item = $deliveryNote->items()->findOrFail($itemInput['delivery_note_item_id']);
                $item->qty_received = $itemInput['qty_received'];
                $item->save();
            }

            $deliveryNote->receipt()->create([
                'received_by' => $user->id,
                'received_photo_path' => $photoPath,
                'received_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $hasDiscrepancy = DiscrepancyReport::where('delivery_note_id', $deliveryNote->id)->exists();

            $deliveryNote->update([
                'status' => $hasDiscrepancy ? DeliveryNote::STATUS_RECEIVED_WITH_DISCREPANCY : DeliveryNote::STATUS_RECEIVED,
            ]);
        });

        return back()->with('success', 'Penerimaan berhasil dikonfirmasi.');
    }

    /**
     * Berita Acara Serah Terima (PDF) — gabungan data kirim + terima.
     */
    public function document(Request $request, DeliveryNote $deliveryNote): Response
    {
        $user = $request->user();
        abort_unless($user->hasRole(Role::GUDANG, Role::OUTLET, Role::ADMIN), 403);
        abort_unless(
            $user->hasRole(Role::ADMIN)
                || $deliveryNote->to_branch_id === $user->branch_id
                || $deliveryNote->from_branch_id === $user->branch_id,
            403
        );

        $deliveryNote->load([
            'fromBranch', 'toBranch', 'sentBy',
            'items.batchLabel.productionBatchItem',
            'items.discrepancy',
            'receipt.receivedBy',
        ]);

        $pdf = Pdf::loadView('scm.deliveries.berita-acara-pdf', [
            'deliveryNote' => $deliveryNote,
        ])->setPaper('a4', 'portrait');

        $filename = 'BA-'.str_replace('/', '-', $deliveryNote->delivery_code).'.pdf';

        return $pdf->stream($filename);
    }
}
