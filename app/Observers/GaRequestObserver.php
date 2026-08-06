<?php

namespace App\Observers;

use App\Models\GA\GaRequest;
use App\Models\Finance\TransactionAccountMapping;
use App\Services\Finance\JournalPoster;

/**
 * Fase 3 Finance — GaRequest yang statusnya jadi "received" (step 3 Finance
 * "diterima", tanda pengajuan selesai & barang/jasa sudah dipakai) otomatis
 * bikin jurnal Beban Operasional (debit) vs Kas (kredit) sebesar total_amount.
 */
class GaRequestObserver
{
    public function updated(GaRequest $gaRequest): void
    {
        if (! $gaRequest->wasChanged('status') || $gaRequest->status !== GaRequest::STATUS_RECEIVED) {
            return;
        }

        JournalPoster::post(
            TransactionAccountMapping::TYPE_GA_REQUEST_RECEIVED,
            (float) $gaRequest->total_amount,
            $gaRequest,
            "Pengajuan GA {$gaRequest->request_number} diterima"
        );
    }
}
