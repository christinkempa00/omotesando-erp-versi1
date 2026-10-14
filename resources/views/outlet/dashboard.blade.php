<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            Dashboard Outlet {{ $branch->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="card-flat p-8 text-center">
                <p class="text-sm text-ink-muted">
                    Selamat datang. Gunakan menu Laporan Kebersihan untuk mengirim dokumentasi opening &amp; closing.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
