<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ChartOfAccount;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chart of Accounts — baca saja di sini (di luar 3 hal eksplisit yang
 * diminta Fase 3: COA, halaman admin mapping, & 3 auto-posting). Kalau
 * nanti perlu CRUD penuh (tambah/edit akun), bisa ditambah menyusul.
 */
class ChartOfAccountController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN, Role::FINANCE), 403);

        $accounts = ChartOfAccount::with('parent')->orderBy('code')->get()
            ->map(fn (ChartOfAccount $account) => [
                'account' => $account,
                'balance' => $account->balance(),
            ]);

        return view('finance.chart-of-accounts.index', [
            'accounts' => $accounts,
            'typeLabels' => ChartOfAccount::typeLabels(),
        ]);
    }
}
