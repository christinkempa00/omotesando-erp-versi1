<?php

namespace App\Services\Finance;

use App\Models\Finance\JournalEntry;
use App\Models\Finance\TransactionAccountMapping;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * SATU-SATUNYA tempat jurnal otomatis boleh dibuat — dipanggil dari
 * Observer (GaRequestObserver, GoodsReceiptObserver, SupplierInvoiceObserver),
 * bukan langsung dari controller. Akun debit/kredit diambil dari
 * transaction_account_mappings (bisa diubah Admin/Finance lewat halaman
 * admin), TIDAK di-hardcode di sini.
 *
 * Mapping tidak ditemukan → GAGAL KERAS (firstOrFail), bukan skip diam-diam.
 * Alasan: kalau silently di-skip, transaksi keuangan riil terjadi TANPA
 * jejak jurnal dan tidak ada yang sadar buku jadi tidak balance. Dalam
 * pemakaian normal ini tidak akan kejadian krn 3 mapping wajib sudah
 * di-seed (lihat TransactionAccountMappingSeeder) — ini cuma jaring
 * pengaman kalau mapping dihapus/konfigurasi rusak.
 */
class JournalPoster
{
    public static function post(string $transactionType, float $amount, Model $reference, ?string $description = null): JournalEntry
    {
        $mapping = TransactionAccountMapping::where('transaction_type', $transactionType)->firstOrFail();

        return DB::transaction(function () use ($mapping, $amount, $reference, $description) {
            $lines = [
                ['account_id' => $mapping->debit_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $mapping->credit_account_id, 'debit' => 0, 'credit' => $amount],
            ];

            $totalDebit = array_sum(array_column($lines, 'debit'));
            $totalCredit = array_sum(array_column($lines, 'credit'));

            // "Validasi wajib: total debit harus sama dengan total credit
            // dalam satu journal_entry, tolak simpan kalau tidak balance."
            // Utk 2-baris simetris ini selalu balance by construction, tapi
            // guard ini generik & reusable kalau nanti ada jurnal multi-baris.
            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new InvalidArgumentException(
                    "Jurnal tidak balance: total debit {$totalDebit} != total credit {$totalCredit}."
                );
            }

            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'entry_date' => now()->toDateString(),
                'reference_type' => $reference::class,
                'reference_id' => $reference->getKey(),
                'description' => $description,
                'created_by' => Auth::id(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry;
        });
    }
}
