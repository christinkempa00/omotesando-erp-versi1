<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman wajib ganti password (akun baru/reset dari IT) — dipaksa lewat
 * middleware EnsurePasswordChanged. Form-nya submit ke route password.update
 * yang SUDAH ada (App\Http\Controllers\Auth\PasswordController::update()),
 * cukup ditambah 1 halaman tampilan di sini.
 */
class ForceChangePasswordController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.force-change-password', [
            'user' => $request->user(),
        ]);
    }
}
