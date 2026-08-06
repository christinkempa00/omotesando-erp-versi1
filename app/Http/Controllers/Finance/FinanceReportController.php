<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\TransactionAccountMapping;
use App\Models\GA\GaRequest;
use App\Models\Purchasing\GoodsReceipt;
use App\Models\Purchasing\SupplierInvoice;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceReportController extends Controller
{
    /**
     * Buku Besar (General Ledger) — mutasi 1 akun dalam periode tertentu +
     * saldo berjalan. Perlu pilih akun dulu (tidak ada default "semua akun"
     * krn volumenya bisa besar).
     */
    public function generalLedger(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $accountId = $request->query('account_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $account = null;
        $lines = collect();

        if ($accountId) {
            $account = ChartOfAccount::findOrFail($accountId);

            $isDebitNormal = in_array($account->type, [ChartOfAccount::TYPE_ASSET, ChartOfAccount::TYPE_EXPENSE], true);

            $rows = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entry_lines.account_id', $accountId)
                ->when($dateFrom, fn ($q) => $q->whereDate('journal_entries.entry_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $dateTo))
                ->orderBy('journal_entries.entry_date')
                ->orderBy('journal_entry_lines.id')
                ->select(
                    'journal_entry_lines.debit',
                    'journal_entry_lines.credit',
                    'journal_entries.entry_number',
                    'journal_entries.entry_date',
                    'journal_entries.description'
                )
                ->get();

            $runningBalance = 0;
            $lines = $rows->map(function ($row) use (&$runningBalance, $isDebitNormal) {
                $runningBalance += $isDebitNormal
                    ? ((float) $row->debit - (float) $row->credit)
                    : ((float) $row->credit - (float) $row->debit);

                return [
                    'entry_number' => $row->entry_number,
                    'entry_date' => $row->entry_date,
                    'description' => $row->description,
                    'debit' => (float) $row->debit,
                    'credit' => (float) $row->credit,
                    'balance' => $runningBalance,
                ];
            });
        }

        return view('finance.reports.general-ledger', [
            'accounts' => ChartOfAccount::orderBy('code')->get(),
            'selectedAccountId' => $accountId,
            'account' => $account,
            'lines' => $lines,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Laporan Beban Operasional & Persediaan per outlet + konsolidasi.
     * journal_entries tidak punya kolom branch_id sendiri — outlet ditelusuri
     * balik lewat reference (morphTo): GaRequest.branch, atau
     * GoodsReceipt.purchaseOrder.branch.
     */
    public function expenseAndInventory(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $expenseAccountId = TransactionAccountMapping::where('transaction_type', TransactionAccountMapping::TYPE_GA_REQUEST_RECEIVED)->value('debit_account_id');
        $inventoryAccountId = TransactionAccountMapping::where('transaction_type', TransactionAccountMapping::TYPE_GOODS_RECEIPT_CREATED)->value('debit_account_id');

        $rows = collect();

        foreach (['expense' => $expenseAccountId, 'inventory' => $inventoryAccountId] as $label => $accountId) {
            if (! $accountId) {
                continue;
            }

            $entries = JournalEntry::with(['lines' => function ($q) use ($accountId) {
                    $q->where('account_id', $accountId);
                }])
                ->with(['reference' => function ($morphTo) {
                    $morphTo->morphWith([
                        GaRequest::class => ['branch'],
                        GoodsReceipt::class => ['purchaseOrder.branch'],
                    ]);
                }])
                ->whereHas('lines', fn ($q) => $q->where('account_id', $accountId))
                ->when($dateFrom, fn ($q) => $q->whereDate('entry_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('entry_date', '<=', $dateTo))
                ->get();

            foreach ($entries as $entry) {
                $branchName = match (true) {
                    $entry->reference instanceof GaRequest => $entry->reference->branch?->name,
                    $entry->reference instanceof GoodsReceipt => $entry->reference->purchaseOrder?->branch?->name,
                    default => null,
                } ?? 'Tidak diketahui';

                $rows->push([
                    'type' => $label,
                    'branch' => $branchName,
                    'amount' => (float) $entry->lines->sum('debit'),
                ]);
            }
        }

        $byBranch = $rows->groupBy('branch')->map(fn ($group) => [
            'expense' => $group->where('type', 'expense')->sum('amount'),
            'inventory' => $group->where('type', 'inventory')->sum('amount'),
        ]);

        return view('finance.reports.expense-inventory', [
            'byBranch' => $byBranch,
            'totalExpense' => $rows->where('type', 'expense')->sum('amount'),
            'totalInventory' => $rows->where('type', 'inventory')->sum('amount'),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Laporan Hutang Usaha (Aging Payable) — supplier_invoices yang belum
     * lunas, dikelompokkan berdasar keterlambatan dari due_date.
     */
    public function payablesAging(): View
    {
        abort_unless(request()->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $invoices = SupplierInvoice::with(['goodsReceipt.purchaseOrder.supplier'])
            ->where('status', '!=', SupplierInvoice::STATUS_PAID)
            ->get();

        $today = now()->startOfDay();

        $buckets = [
            'not_due' => collect(),
            'd1_30' => collect(),
            'd31_60' => collect(),
            'd60_plus' => collect(),
        ];

        foreach ($invoices as $invoice) {
            // Positif kalau sudah lewat jatuh tempo (due_date di masa lalu),
            // negatif/0 kalau belum jatuh tempo.
            $daysOverdue = $invoice->due_date->copy()->startOfDay()->diffInDays($today, false);
            $outstanding = (float) $invoice->amount - (float) $invoice->paid_amount;

            $row = [
                'invoice' => $invoice,
                'supplier' => $invoice->goodsReceipt?->purchaseOrder?->supplier?->name ?? '—',
                'outstanding' => $outstanding,
                'days_overdue' => $daysOverdue,
            ];

            match (true) {
                $daysOverdue <= 0 => $buckets['not_due']->push($row),
                $daysOverdue <= 30 => $buckets['d1_30']->push($row),
                $daysOverdue <= 60 => $buckets['d31_60']->push($row),
                default => $buckets['d60_plus']->push($row),
            };
        }

        return view('finance.reports.payables-aging', [
            'buckets' => $buckets,
            'totals' => collect($buckets)->map(fn ($b) => $b->sum('outstanding')),
        ]);
    }

    /**
     * Neraca sederhana — saldo akhir tiap akun Aset/Liabilitas/Ekuitas.
     * SENGAJA tidak closing pendapatan/beban ke ekuitas otomatis
     * (simplifikasi sesuai kata "sederhana" di spek).
     */
    public function balanceSheet(): View
    {
        abort_unless(request()->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $accounts = ChartOfAccount::whereIn('type', [
            ChartOfAccount::TYPE_ASSET,
            ChartOfAccount::TYPE_LIABILITY,
            ChartOfAccount::TYPE_EQUITY,
        ])->orderBy('code')->get();

        $rows = $accounts->map(fn (ChartOfAccount $account) => [
            'account' => $account,
            'balance' => $account->balance(),
        ]);

        return view('finance.reports.balance-sheet', [
            'rows' => $rows,
            'totalAsset' => $rows->where('account.type', ChartOfAccount::TYPE_ASSET)->sum('balance'),
            'totalLiability' => $rows->where('account.type', ChartOfAccount::TYPE_LIABILITY)->sum('balance'),
            'totalEquity' => $rows->where('account.type', ChartOfAccount::TYPE_EQUITY)->sum('balance'),
        ]);
    }
}
