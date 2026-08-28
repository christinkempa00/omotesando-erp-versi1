<?php

namespace App\Models\Concerns;

use App\Models\BranchLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dipakai model GA yang punya branch_id + branch_location_id (Asset,
 * UniformStock, UniformRecord, WorkLog, MaintenanceJob, GaRequest,
 * GaQuickRequest) — semuanya sudah punya branch(): BelongsTo sendiri,
 * trait ini cuma menambah relasi Cabang + label gabungan siap-pakai.
 */
trait HasBranchLocation
{
    public function branchLocation(): BelongsTo
    {
        return $this->belongsTo(BranchLocation::class);
    }

    public function outletLabel(): ?string
    {
        if (! $this->branch) {
            return null;
        }

        return $this->branchLocation
            ? "{$this->branch->name} — {$this->branchLocation->name}"
            : $this->branch->name;
    }
}
