<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\View\View;

/**
 * Referensi visual & komponen inti Allez ERP — versi hidup di dalam app
 * (bukan cuma mockup di claude.ai/design), dipakai konsisten lintas modul
 * (GA, SCM, IT, Head) supaya user tidak belajar ulang pola UI tiap pindah
 * modul. Lihat .design-sync/NOTES.md utk sumber desain & keputusan scope.
 */
class DesignSystemController extends Controller
{
    public function index(): View
    {
        $demoSteps = collect([
            new Approval(['step' => 1, 'approver_role' => 'Staff', 'status' => Approval::STATUS_APPROVED, 'approved_at' => now()->subDays(2)]),
            new Approval(['step' => 2, 'approver_role' => 'Manager', 'status' => Approval::STATUS_PENDING]),
            new Approval(['step' => 3, 'approver_role' => 'Head', 'status' => Approval::STATUS_PENDING]),
        ]);

        $rejectedSteps = collect([
            new Approval(['step' => 1, 'approver_role' => 'Manager Outlet', 'status' => Approval::STATUS_APPROVED, 'approved_at' => now()->subDays(3)]),
            new Approval(['step' => 2, 'approver_role' => 'Head', 'status' => Approval::STATUS_REJECTED, 'approved_at' => now()->subDays(1), 'note' => 'Anggaran belum tersedia']),
        ]);

        $demoQr = (new Builder(writer: new SvgWriter()))->build(
            data: 'BTC-0819-A',
            size: 140,
            margin: 6,
        )->getString();

        return view('it.design-system.index', [
            'demoSteps' => $demoSteps,
            'rejectedSteps' => $rejectedSteps,
            'demoQr' => $demoQr,
        ]);
    }
}
