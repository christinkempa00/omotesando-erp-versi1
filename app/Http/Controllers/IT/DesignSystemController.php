<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Referensi visual & komponen inti Allez ERP — versi hidup di dalam app
 * (bukan cuma mockup di claude.ai/design), dipakai konsisten lintas modul
 * (GA, IT, Head, Outlet) supaya user tidak belajar ulang pola UI tiap
 * pindah modul. Lihat .design-sync/NOTES.md utk sumber desain & keputusan
 * scope.
 */
class DesignSystemController extends Controller
{
    public function index(): View
    {
        return view('it.design-system.index');
    }
}
