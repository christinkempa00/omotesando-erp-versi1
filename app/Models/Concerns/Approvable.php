<?php

namespace App\Models\Concerns;

use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * Dipakai oleh model manapun yang butuh alur approval berjenjang
 * (GaRequest, dan nanti Transaction/PurchaseOrder di modul lain).
 *
 * Model pemakai trait ini WAJIB implement syncStatusAfterApproval(): void —
 * dipanggil otomatis tiap kali sebuah step di-approve/reject, supaya kolom
 * status milik model tsb (kosakata beda-beda per model) ikut ter-update
 * tanpa trait generik ini perlu tahu nama/konstanta status-nya.
 */
trait Approvable
{
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable')->orderBy('step');
    }

    /**
     * Step approval yang sedang menunggu (paling kecil, status pending).
     */
    public function currentApprovalStep(): ?Approval
    {
        return $this->approvals()
            ->where('status', Approval::STATUS_PENDING)
            ->orderBy('step')
            ->first();
    }

    /**
     * Pastikan step sebelum $step ini sudah semua approved,
     * supaya approval tidak bisa dilompati.
     */
    public function canApproveStep(int $step): bool
    {
        $blockingCount = $this->approvals()
            ->where('step', '<', $step)
            ->where('status', '!=', Approval::STATUS_APPROVED)
            ->count();

        return $blockingCount === 0;
    }

    public function isFullyApproved(): bool
    {
        return $this->approvals()->count() > 0
            && $this->approvals()->where('status', '!=', Approval::STATUS_APPROVED)->doesntExist();
    }

    public function hasRejectedStep(): bool
    {
        return $this->approvals()->where('status', Approval::STATUS_REJECTED)->exists();
    }

    /**
     * Setujui step yang sedang menunggu, KALAU $user punya role approver step
     * itu. Mengembalikan false (tanpa melakukan apa-apa) kalau tidak ada step
     * pending atau $user bukan approver yang berwenang di step tsb — dipakai
     * controller utk keputusan 403/pesan error, bukan melempar exception.
     *
     * Dibungkus transaction + lockForUpdate supaya aman kalau 2 approver
     * memproses step yang sama nyaris bersamaan — hanya satu yang berhasil,
     * yang kedua akan menemukan status sudah bukan pending lagi lalu gagal
     * dengan tenang (return false), bukan dobel ter-approve.
     */
    public function approveCurrentStepBy(User $user, ?string $note = null, ?string $signaturePath = null): bool
    {
        $approved = DB::transaction(function () use ($user, $note, $signaturePath) {
            $approval = $this->approvals()
                ->where('status', Approval::STATUS_PENDING)
                ->orderBy('step')
                ->lockForUpdate()
                ->first();

            if (! $approval || ! $user->hasRole($approval->approver_role)) {
                return false;
            }

            $approval->update([
                'status' => Approval::STATUS_APPROVED,
                'approver_user_id' => $user->id,
                'approved_at' => now(),
                'note' => $note,
                'signature_path' => $signaturePath ?? $approval->signature_path,
            ]);

            $this->syncStatusAfterApproval();

            return true;
        });

        if ($approved) {
            $this->sendFinalDocumentIfComplete();
        }

        return $approved;
    }

    /**
     * Tolak step yang sedang menunggu. Semantik & keamanan sama dengan
     * approveCurrentStepBy (transaction + lockForUpdate anti dobel-proses).
     * Sengaja TIDAK menerima tanda tangan — reject tidak butuh tanda tangan
     * (cuma approve yang wajib, lihat approveCurrentStepBy).
     */
    public function rejectCurrentStepBy(User $user, ?string $note = null): bool
    {
        $rejected = DB::transaction(function () use ($user, $note) {
            $approval = $this->approvals()
                ->where('status', Approval::STATUS_PENDING)
                ->orderBy('step')
                ->lockForUpdate()
                ->first();

            if (! $approval || ! $user->hasRole($approval->approver_role)) {
                return false;
            }

            $approval->update([
                'status' => Approval::STATUS_REJECTED,
                'approver_user_id' => $user->id,
                'approved_at' => now(),
                'note' => $note,
            ]);

            $this->syncStatusAfterApproval();

            return true;
        });

        return $rejected;
    }

    /**
     * Hook opsional dipanggil tiap kali sebuah step disetujui — no-op secara
     * default. Model pemakai trait ini boleh override untuk mengirim dokumen
     * final (mis. PDF bertanda tangan) sekali alur approval selesai penuh
     * (lihat GaRequest::sendFinalDocumentIfComplete()).
     */
    protected function sendFinalDocumentIfComplete(): void
    {
        //
    }
}
