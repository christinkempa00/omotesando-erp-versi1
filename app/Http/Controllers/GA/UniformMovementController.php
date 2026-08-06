<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GA\UniformMovement;
use App\Models\GA\UniformStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniformMovementController extends Controller
{
    public function index(Request $request): View
    {
        $query = UniformMovement::with(['uniformStock.branch', 'createdBy']);

        if ($stockId = $request->query('uniform_stock_id')) {
            $query->where('uniform_stock_id', $stockId);
        }

        if ($type = $request->query('type')) {
            $query->where('movement_type', $type);
        }

        if ($branchId = $request->query('branch_id')) {
            $query->whereHas('uniformStock', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('movement_code', 'like', "%{$search}%")
                    ->orWhereHas('uniformStock', fn ($sq) => $sq->where('uniform_type', 'like', "%{$search}%"));
            });
        }

        $movements = $query->latest()->paginate(20)->withQueryString();

        return view('ga.uniforms.movements.index', [
            'movements' => $movements,
            'typeLabels' => UniformMovement::typeLabels(),
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'stocks' => UniformStock::orderBy('uniform_type')->get(),
            'selectedType' => $type,
            'selectedBranch' => $branchId,
            'selectedStock' => $stockId,
            'search' => $search,
        ]);
    }
}
