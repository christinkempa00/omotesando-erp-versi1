<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  string  $sidebar  Partial sidebar yang dipakai: 'ga' (default, tidak
     *                           berubah untuk semua halaman GA yang sudah ada),
     *                           'head' untuk halaman di bawah /head, atau 'it'
     *                           untuk halaman di bawah /it (Kontrol Akses & Mode
     *                           Pemeliharaan).
     */
    public function __construct(
        public string $sidebar = 'ga',
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
