<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

abstract class Controller
{
    /**
     * Beberapa controller GA dipakai bersama oleh 2 route group (ga.* dan
     * outlet.*, lihat routes/web.php) — data yang dikirim sama persis,
     * cuma template Blade yang beda (Outlet dapat versi lebih sederhana,
     * tier-aware). Nama route saat ini yang menentukan prefix view mana
     * yang dirender, bukan role — supaya tidak perlu query tambahan.
     */
    protected function viewFor(string $name, array $data = []): View
    {
        $prefix = str_starts_with((string) request()->route()?->getName(), 'outlet.') ? 'outlet' : 'ga';

        return view("{$prefix}.{$name}", $data);
    }

    /**
     * Sama seperti viewFor() tapi utk nama route (redirect ke halaman lain
     * dalam portal yang sama, mis. ga.requests.index vs outlet.requests.index).
     */
    protected function routeFor(string $name): string
    {
        $prefix = str_starts_with((string) request()->route()?->getName(), 'outlet.') ? 'outlet' : 'ga';

        return "{$prefix}.{$name}";
    }
}
