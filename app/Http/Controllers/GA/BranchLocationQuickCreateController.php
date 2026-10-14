<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Models\BranchLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Endpoint kecil dipanggil via fetch dari dropdown "Cabang" di form-form
 * GA/Outlet (lihat <x-branch-location-select>) supaya user bisa tambah
 * Cabang baru langsung dari form, tanpa minta IT. Dipakai bersama oleh
 * grup route ga. dan outlet. (lihat routes/web.php) — sama polanya dgn
 * App\Http\Controllers\IT\QuickCreateController utk Divisi/Branch.
 */
class BranchLocationQuickCreateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branch_locations', 'name')->where(
                    fn ($q) => $q->where('branch_id', $request->input('branch_id'))
                ),
            ],
        ], [
            'name.unique' => 'Cabang dengan nama ini sudah ada untuk outlet tersebut.',
        ]);

        $location = BranchLocation::create([
            'branch_id' => $validated['branch_id'],
            'name' => $validated['name'],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return response()->json([
            'id' => $location->id,
            'name' => $location->name,
            'branch_id' => $location->branch_id,
        ], 201);
    }
}
