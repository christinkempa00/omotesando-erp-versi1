<?php

namespace App\Models\Concerns;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Dipakai oleh model manapun yang butuh alur approval berjenjang
 * (GaRequest, dan nanti Transaction/PurchaseOrder di modul lain).
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
}
