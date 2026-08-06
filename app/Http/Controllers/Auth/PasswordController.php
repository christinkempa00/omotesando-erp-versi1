<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsToRoleHome;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    use RedirectsToRoleHome;

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Dicek SEBELUM update — dipakai di bawah utk tahu ini ganti password
        // wajib (akun baru/reset IT, lihat EnsurePasswordChanged) atau ganti
        // password sukarela biasa dari halaman Profile.
        $wasForced = $request->user()->password_must_change;

        // Password lama HANYA wajib diisi utk ganti sukarela (Profile) — alur
        // paksa (password sementara dari IT) tidak mensyaratkannya, user
        // memang belum tentu tahu/ingat password sementara itu dgn pasti.
        $rules = ['password' => ['required', Password::defaults(), 'confirmed']];

        if (! $wasForced) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validateWithBag('updatePassword', $rules);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'password_must_change' => false,
        ]);

        if ($wasForced) {
            return $this->redirectToRoleHome($request->user());
        }

        return back()->with('status', 'password-updated');
    }
}
