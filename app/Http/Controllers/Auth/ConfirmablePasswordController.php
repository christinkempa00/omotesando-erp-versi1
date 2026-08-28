<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsToRoleHome;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    use RedirectsToRoleHome;

    /**
     * Show the confirm password view.
     */
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        // BUKAN ->intended() — sama seperti RedirectsToRoleHome (lihat
        // docblock trait itu): URL intended di session bisa saja halaman
        // milik role LAIN dari percobaan akses sebelumnya, dan
        // route('dashboard') sendiri di-gate role:GA,Admin (403 utk IT/Head).
        return $this->redirectToRoleHome($request->user());
    }
}
