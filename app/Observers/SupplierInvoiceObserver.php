<?php

namespace App\Observers;

use App\Models\Finance\TransactionAccountMapping;
use App\Models\Purchasing\SupplierInvoice;
use App\Services\Finance\JournalPoster;

/**
 * Fase 3 Finance — SupplierInvoice yang statusnya jadi "paid" (LUNAS penuh,
 * bukan tiap cicilan/partial) otomatis bikin jurnal Hutang Usaha (debit) vs
 * Kas (kredit) sebesar TOTAL invoice (bukan jumlah cicilan terakhir).
 */
class SupplierInvoiceObserver
{
    public function updated(SupplierInvoice $supplierInvoice): void
    {
        if (! $supplierInvoice->wasChanged('status') || $supplierInvoice->status !== SupplierInvoice::STATUS_PAID) {
            return;
        }

        JournalPoster::post(
            TransactionAccountMapping::TYPE_SUPPLIER_INVOICE_PAID,
            (float) $supplierInvoice->amount,
            $supplierInvoice,
            "Invoice supplier {$supplierInvoice->invoice_number} lunas"
        );
    }
}
