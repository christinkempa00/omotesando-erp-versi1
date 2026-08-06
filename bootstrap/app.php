<?php

use App\Http\Middleware\CheckModuleMaintenance;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\ModuleAccessMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // routes/deploy.php didaftarkan lewat then: (bukan web:) supaya
        // TIDAK ikut middleware group 'web' — lihat komentar di file itu.
        then: function () {
            require __DIR__.'/../routes/deploy.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'module' => ModuleAccessMiddleware::class,
            'module.maintenance' => CheckModuleMaintenance::class,
        ]);

        // Global di semua route 'web' — user yang wajib ganti password
        // (akun baru/reset dari IT) di-redirect paksa ke halaman ganti
        // password dulu, apa pun halaman yang mereka coba akses.
        $middleware->appendToGroup('web', EnsurePasswordChanged::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();