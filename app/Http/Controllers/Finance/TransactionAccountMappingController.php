<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateTransactionAccountMappingRequest;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\TransactionAccountMapping;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman admin utk mengatur akun debit/kredit tiap jenis transaksi yang
 * di-auto-post JournalPoster — supaya tidak di-hardcode di kode. Hanya
 * index+edit (bukan create/delete) krn transaction_type dipetakan 1:1 ke
 * observer yang sudah ada di kode (GaRequestObserver, GoodsReceiptObserver,
 * SupplierInvoiceObserver) — menambah transaction_type baru lewat UI tidak
 * ada gunanya tanpa observer yang memicunya juga.
 */
class TransactionAccountMappingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $mappings = TransactionAccountMapping::with(['debitAccount', 'creditAccount'])->get();

        return view('finance.transaction-mappings.index', [
            'mappings' => $mappings,
            'typeLabels' => TransactionAccountMapping::transactionTypeLabels(),
        ]);
    }

    public function edit(Request $request, TransactionAccountMapping $transactionMapping): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        return view('finance.transaction-mappings.edit', [
            'mapping' => $transactionMapping,
            'accounts' => ChartOfAccount::orderBy('code')->get(),
            'typeLabels' => TransactionAccountMapping::transactionTypeLabels(),
        ]);
    }

    public function update(UpdateTransactionAccountMappingRequest $request, TransactionAccountMapping $transactionMapping): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $transactionMapping->update($request->validated());

        return redirect()
            ->route('finance.transaction-mappings.index')
            ->with('success', 'Mapping akun berhasil diperbarui.');
    }
}
