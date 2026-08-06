<?php

use App\Http\Controllers\DeployTaskController;
use Illuminate\Support\Facades\Route;

/**
 * SENGAJA didaftarkan lewat callback then: di bootstrap/app.php, BUKAN
 * lewat routes/web.php — supaya route ini tidak ikut middleware group
 * 'web' (session/cookie/CSRF), karena harus tetap jalan di database yang
 * benar-benar kosong (belum di-migrate). Lihat DeployTaskController utk
 * penjelasan keamanan lengkap & cara menonaktifkannya setelah dipakai.
 */
Route::get('/deploy-tasks/{token}', [DeployTaskController::class, 'confirm']);
Route::post('/deploy-tasks/{token}', [DeployTaskController::class, 'run']);
