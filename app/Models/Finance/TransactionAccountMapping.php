<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pemetaan akun debit/kredit per jenis transaksi — dibaca
 * App\Services\Finance\JournalPoster setiap kali sebuah event (GaRequest
 * received, GoodsReceipt dibuat, SupplierInvoice lunas) memicu jurnal
 * otomatis. Diedit lewat halaman admin (TransactionAccountMappingController),
 * BUKAN di-hardcode di kode.
 */
class TransactionAccountMapping extends Model
{
    protected $fillable = [
        'transaction_type',
        'debit_account_id',
        'credit_account_id',
    ];

    public const TYPE_GA_REQUEST_RECEIVED = 'ga_request_received';
    public const TYPE_GOODS_RECEIPT_CREATED = 'goods_receipt_created';
    public const TYPE_SUPPLIER_INVOICE_PAID = 'supplier_invoice_paid';

    public static function transactionTypeLabels(): array
    {
        return [
            self::TYPE_GA_REQUEST_RECEIVED => 'Pengajuan GA Diterima',
            self::TYPE_GOODS_RECEIPT_CREATED => 'Barang Diterima dari Supplier',
            self::TYPE_SUPPLIER_INVOICE_PAID => 'Invoice Supplier Lunas',
        ];
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_account_id');
    }
}
