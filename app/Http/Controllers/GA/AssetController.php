<?php

namespace App\Http\Controllers\GA;

use App\Http\Controllers\Controller;
use App\Http\Requests\GA\StoreAssetRequest;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\GA\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);

        $assets = $query->latest()->paginate(15)->withQueryString();

        return view('ga.assets.index', [
            'assets' => $assets,
            'statusLabels' => Asset::statusLabels(),
            'branches' => Branch::orderedOutlets(Branch::GA_OUTLETS),
            'selectedStatus' => $request->query('status'),
            'selectedBranch' => $request->query('branch_id'),
            'search' => $request->query('search'),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
        ]);
    }

    public function create(): View
    {
        return view('ga.assets.create', [
            'statusLabels' => Asset::statusLabels(),
            'branches' => Branch::orderedOutlets(Branch::GA_OUTLETS),
            'branchLocations' => BranchLocation::groupedByBranch(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $asset = new Asset($validated);
        $asset->asset_code = Asset::generateAssetCode();
        $asset->created_by = $request->user()->id;

        if ($request->hasFile('image')) {
            $asset->image_path = $request->file('image')->store('assets/photos', 'public');
        }

        if ($request->hasFile('serial_number_photo')) {
            $asset->serial_number_photo_path = $request->file('serial_number_photo')->store('assets/serial-photos', 'public');
        }

        $asset->save();

        return redirect()
            ->route('ga.assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil ditambahkan.");
    }

    public function show(Asset $asset): View
    {
        $asset->load(['branch', 'branchLocation', 'createdBy']);

        return view('ga.assets.show', [
            'asset' => $asset,
            'statusLabels' => Asset::statusLabels(),
        ]);
    }

    public function edit(Asset $asset): View
    {
        return view('ga.assets.edit', [
            'asset' => $asset,
            'statusLabels' => Asset::statusLabels(),
            'branches' => Branch::orderedOutlets(Branch::GA_OUTLETS, $asset->branch?->name),
            'branchLocations' => BranchLocation::groupedByBranch(),
        ]);
    }

    public function update(StoreAssetRequest $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validated();

        $asset->fill($validated);

        if ($request->hasFile('image')) {
            if ($asset->image_path) {
                Storage::disk('public')->delete($asset->image_path);
            }
            $asset->image_path = $request->file('image')->store('assets/photos', 'public');
        }

        if ($request->hasFile('serial_number_photo')) {
            if ($asset->serial_number_photo_path) {
                Storage::disk('public')->delete($asset->serial_number_photo_path);
            }
            $asset->serial_number_photo_path = $request->file('serial_number_photo')->store('assets/serial-photos', 'public');
        }

        $asset->save();

        return redirect()
            ->route('ga.assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil diperbarui.");
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        if ($asset->image_path) {
            Storage::disk('public')->delete($asset->image_path);
        }
        if ($asset->serial_number_photo_path) {
            Storage::disk('public')->delete($asset->serial_number_photo_path);
        }

        $asset->delete();

        return redirect()
            ->route('ga.assets.index')
            ->with('success', 'Aset berhasil dihapus.');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $assets = $this->filteredQuery($request)->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris Aset');

        $header = ['ID Aset', 'Nama Aset', 'Merk', 'Outlet', 'Lokasi', 'Tanggal Beli', 'Status', 'Penanggung Jawab', 'Jumlah'];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $statusLabels = Asset::statusLabels();
        $row = 2;
        foreach ($assets as $asset) {
            $sheet->fromArray([
                $asset->asset_code,
                $asset->name,
                $asset->brand,
                $asset->branch?->name,
                $asset->location,
                optional($asset->purchase_date)->format('d/m/y'),
                $statusLabels[$asset->status] ?? $asset->status,
                $asset->custodian_name,
                $asset->quantity,
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'inventaris-aset-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $assets = $this->filteredQuery($request)->latest()->get();

        $pdf = Pdf::loadView('ga.assets.export-pdf', [
            'assets' => $assets,
            'statusLabels' => Asset::statusLabels(),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('inventaris-aset-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Query dasar yang dipakai bersama oleh index() dan kedua export — supaya
     * hasil export selalu konsisten dengan filter yang sedang aktif di layar.
     */
    private function filteredQuery(Request $request)
    {
        $query = Asset::with(['branch', 'branchLocation']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('purchase_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('purchase_date', '<=', $dateTo);
        }

        return $query;
    }
}
