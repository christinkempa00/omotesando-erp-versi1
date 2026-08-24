<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\OutletInspectionAreaRequest;
use App\Models\Branch;
use App\Models\GA\OutletInspectionArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OutletInspectionAreaController extends Controller
{
    public function index(): View
    {
        $branches = Branch::orderedOutlets(Branch::MONITORING_OUTLETS);

        $activeCounts = OutletInspectionArea::whereIn('branch_id', $branches->pluck('id'))
            ->where('is_active', true)
            ->selectRaw('branch_id, count(*) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        return view('ga.outlet-areas.index', [
            'branches' => $branches,
            'activeCounts' => $activeCounts,
        ]);
    }

    /**
     * Satu halaman utk daftar area + tambah + edit (query ?edit={id} yang
     * menentukan mode edit) — sengaja tidak dipecah jadi create/edit
     * terpisah, area cuma py 2 field (nama/urutan) jadi tidak worth
     * halaman sendiri-sendiri.
     */
    public function manage(Request $request, Branch $branch): View
    {
        abort_unless(in_array($branch->name, Branch::MONITORING_OUTLETS, true), 404);

        $areas = OutletInspectionArea::where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $editingArea = null;
        if ($editId = $request->query('edit')) {
            $editingArea = $areas->firstWhere('id', (int) $editId);
        }

        return view('ga.outlet-areas.manage', [
            'branch' => $branch,
            'areas' => $areas,
            'editingArea' => $editingArea,
        ]);
    }

    /**
     * sort_order otomatis — area baru selalu ditambahkan di AKHIR daftar
     * (max sort_order + 1), tidak lagi diinput manual. Urutan selanjutnya
     * diatur GA lewat drag di tabel (lihat reorder()).
     */
    public function store(OutletInspectionAreaRequest $request, Branch $branch): RedirectResponse
    {
        $nextOrder = OutletInspectionArea::where('branch_id', $branch->id)->max('sort_order');

        OutletInspectionArea::create([
            ...$request->validated(),
            'sort_order' => $nextOrder === null ? 0 : $nextOrder + 1,
        ]);

        return redirect()
            ->route('ga.outlet-areas.manage', $branch)
            ->with('success', 'Area pemeriksaan berhasil ditambahkan.');
    }

    /**
     * branch_id SENGAJA tidak ikut di-update meski divalidasi (lihat
     * OutletInspectionAreaRequest) — area tidak boleh pindah outlet lewat
     * form edit ini. sort_order juga tidak disentuh di sini — itu urusan
     * reorder() lewat drag, bukan form nama.
     */
    public function update(OutletInspectionAreaRequest $request, Branch $branch, OutletInspectionArea $area): RedirectResponse
    {
        abort_unless($area->branch_id === $branch->id, 404);

        $area->update($request->safe()->only(['name']));

        return redirect()
            ->route('ga.outlet-areas.manage', $branch)
            ->with('success', 'Area pemeriksaan berhasil diperbarui.');
    }

    /**
     * Dipanggil AJAX (fetch) tiap kali GA selesai drag-reorder area di
     * tabel — satu request per area yang posisinya berubah (pola sama
     * ItBoardController/persistColumnOrder di Papan Kerja IT: PATCH
     * ringan per-item, bukan satu payload batch). Endpoint terpisah dari
     * update() supaya tidak perlu ikut validasi `name` yg tidak relevan
     * di sini.
     */
    public function reorder(Request $request, Branch $branch, OutletInspectionArea $area): JsonResponse
    {
        abort_unless($area->branch_id === $branch->id, 404);

        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $area->update(['sort_order' => $validated['sort_order']]);

        return response()->json(['success' => true]);
    }

    public function toggleActive(Branch $branch, OutletInspectionArea $area): RedirectResponse
    {
        abort_unless($area->branch_id === $branch->id, 404);

        $area->update(['is_active' => ! $area->is_active]);

        return back()->with('success', $area->is_active ? 'Area diaktifkan kembali.' : 'Area dinonaktifkan.');
    }

    /**
     * Hapus permanen HANYA kalau area belum pernah dipakai laporan mana pun
     * (lihat OutletInspectionArea::hasBeenUsed() — guard-nya, TODO Fase B-2).
     * Kalau sudah pernah dipakai, arahkan ke nonaktifkan saja (toggleActive).
     */
    public function destroy(Branch $branch, OutletInspectionArea $area): RedirectResponse
    {
        abort_unless($area->branch_id === $branch->id, 404);

        if ($area->hasBeenUsed()) {
            return back()->withErrors([
                'area' => 'Area ini sudah pernah dipakai di laporan — nonaktifkan saja, tidak bisa dihapus permanen.',
            ]);
        }

        $area->delete();

        return redirect()
            ->route('ga.outlet-areas.manage', $branch)
            ->with('success', 'Area pemeriksaan berhasil dihapus.');
    }
}
