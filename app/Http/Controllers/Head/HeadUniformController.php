<?php

namespace App\Http\Controllers\Head;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GA\UniformStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monitoring stok seragam untuk Head — read-only (reuse model UniformStock &
 * query filter yang sama dengan UniformStockController), tanpa aksi
 * restock/issue/adjustment/disposal.
 */
class HeadUniformController extends Controller
{
    public function index(Request $request): View
    {
        $query = UniformStock::with('branch');

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('uniform_type', 'like', "%{$search}%")
                    ->orWhere('stock_code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $stocks = $query->orderBy('uniform_type')->paginate(15)->withQueryString();

        $summary = [
            'total_variants' => UniformStock::count(),
            'total_available' => (int) UniformStock::sum('available_stock'),
            'total_unusable' => (int) UniformStock::sum('unusable_stock'),
            'low_stock_count' => UniformStock::lowStock()->count(),
        ];

        return view('head.uniforms.index', [
            'stocks' => $stocks,
            'summary' => $summary,
            'branches' => Branch::orderedOutlets(UniformStock::UNIFORM_OUTLETS),
            'selectedBranch' => $branchId,
            'search' => $search,
            'lowStockOnly' => $request->boolean('low_stock'),
        ]);
    }
}
