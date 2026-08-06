<?php

namespace App\Http\Controllers;

use App\Models\SystemModule;
use Illuminate\View\View;

/**
 * Tujuan redirect CheckModuleMaintenance middleware — ditampilkan ke user
 * (selain IT/Admin) yang mencoba akses halaman yang sedang ditandai IT
 * "Dalam Pemeliharaan".
 */
class MaintenanceNoticeController extends Controller
{
    public function show(string $key): View
    {
        $module = SystemModule::where('key', $key)->firstOrFail();

        // Halaman head.* hanya bisa diakses role Head (lihat middleware role
        // di routes/web.php), jadi aman menentukan sidebar & tombol kembali
        // dari prefix key tanpa perlu cek role user lagi di sini.
        $isHeadModule = str_starts_with($key, 'head.');
        $backRoute = $isHeadModule ? 'head.dashboard' : 'dashboard';

        // Hindari redirect-loop: kalau yang lagi dalam pemeliharaan justru
        // Dashboard Head itu sendiri, tombol "kembali" jangan mengarah ke
        // situ lagi — arahkan ke profile (selalu bisa diakses semua role).
        if ($key === SystemModule::HEAD_DASHBOARD) {
            $backRoute = 'profile.edit';
        }

        return view('maintenance-notice', [
            'module' => $module,
            'sidebar' => $isHeadModule ? 'head' : 'ga',
            'backRoute' => $backRoute,
        ]);
    }
}
