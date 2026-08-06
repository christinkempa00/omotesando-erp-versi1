<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsToRoleHome;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    use RedirectsToRoleHome;

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Akun baru/reset dari IT wajib ganti password dulu — langsung ke
        // situ (bukan ke role home dulu baru di-redirect lagi oleh
        // EnsurePasswordChanged, yang juga akan menangkap ini di request
        // berikutnya kalau sampai lolos dari sini).
        if ($request->user()->password_must_change) {
            return redirect()->route('password.force-change');
        }

        // Role Head & IT punya dashboard/halaman sendiri (terpisah dari GA);
        // role lain (GA, Admin, Finance, dst.) tetap ke dashboard seperti
        // sebelumnya — lihat RedirectsToRoleHome utk alasan tidak pakai
        // ->intended().
        return $this->redirectToRoleHome($request->user());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Redirect langsung ke halaman login (bukan '/' / welcome page Laravel
        // default) supaya gampang login lagi pakai akun lain (mis. GA setelah
        // sebelumnya login sebagai Head) tanpa harus cari link login dulu.
        return redirect()->route('login');
    }
}
