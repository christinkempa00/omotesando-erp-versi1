<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StoreSupplierRequest;
use App\Models\Purchasing\Supplier;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Data master supplier — dikelola Admin saja. Cost Control/GA cuma butuh
 * baca (dipakai saat pilih supplier waktu bikin Purchase Order).
 */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->hasRole(Role::PURCHASING, Role::GA, Role::FINANCE, Role::ADMIN),
            403
        );

        $suppliers = Supplier::orderBy('name')->paginate(20)->withQueryString();

        return view('purchasing.suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        return view('purchasing.suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('purchasing.suppliers.index')
            ->with('success', "Supplier {$supplier->name} berhasil ditambahkan.");
    }

    public function edit(Request $request, Supplier $supplier): View
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        return view('purchasing.suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::ADMIN), 403);

        $supplier->update($request->validated());

        return redirect()
            ->route('purchasing.suppliers.index')
            ->with('success', "Supplier {$supplier->name} berhasil diperbarui.");
    }
}
