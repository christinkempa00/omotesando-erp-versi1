<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Support\RoleHomeResolver;
use Illuminate\Http\RedirectResponse;

/**
 * Halaman "rumah" per role — dipakai setelah login (AuthenticatedSessionController)
 * DAN setelah user berhasil ganti password wajib (PasswordController) supaya
 * logic-nya tidak dobel di 2 tempat. Nama route-nya sendiri ada di
 * RoleHomeResolver (dipakai juga oleh override RedirectIfAuthenticated di
 * AppServiceProvider — lihat docblock di sana).
 *
 * SENGAJA pakai redirect()->route() polos, BUKAN ->intended() — intended()
 * memprioritaskan URL yang tersimpan di session dari percobaan akses SEBELUM
 * login/ganti-password, yang bisa saja halaman milik role LAIN dan langsung
 * 403 begitu sampai. Konsekuensinya: tidak ada "kembali ke halaman yang tadi
 * dituju", tapi correctness (selalu mendarat di halaman yang benar) lebih
 * penting — lihat riwayat bug redirect Purchasing sebelumnya.
 */
trait RedirectsToRoleHome
{
    protected function redirectToRoleHome(User $user): RedirectResponse
    {
        return redirect()->route(RoleHomeResolver::routeNameFor($user));
    }
}
