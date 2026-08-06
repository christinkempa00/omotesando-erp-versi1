<?php

namespace Database\Seeders;

use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\TransactionAccountMapping;
use Illuminate\Database\Seeder;

class TransactionAccountMappingSeeder extends Seeder
{
    /**
     * 3 mapping wajib Fase 3 — tanpa ini, JournalPoster akan GAGAL (firstOrFail,
     * sengaja bukan skip diam-diam) saat event terkait terjadi.
     */
    public function run(): void
    {
        $accountId = fn (string $code) => ChartOfAccount::where('code', $code)->value('id');

        $mappings = [
            [
                'transaction_type' => TransactionAccountMapping::TYPE_GA_REQUEST_RECEIVED,
                'debit' => '5100', // Beban Operasional
                'credit' => '1100', // Kas
            ],
            [
                'transaction_type' => TransactionAccountMapping::TYPE_GOODS_RECEIPT_CREATED,
                'debit' => '1200', // Persediaan
                'credit' => '2100', // Hutang Usaha
            ],
            [
                'transaction_type' => TransactionAccountMapping::TYPE_SUPPLIER_INVOICE_PAID,
                'debit' => '2100', // Hutang Usaha
                'credit' => '1100', // Kas
            ],
        ];

        foreach ($mappings as $data) {
            TransactionAccountMapping::updateOrCreate(
                ['transaction_type' => $data['transaction_type']],
                [
                    'debit_account_id' => $accountId($data['debit']),
                    'credit_account_id' => $accountId($data['credit']),
                ]
            );
        }
    }
}
