<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Outlet {{ $branch->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('outlet.requests.index') }}" class="glass-panel p-5 hover:shadow-md transition">
                    <p class="text-xs text-gray-400 mb-1">Draft Pengajuan</p>
                    <p class="text-2xl font-semibold text-gray-800">{{ $draftRequestCount }}</p>
                </a>
                <a href="{{ route('outlet.uniforms.stocks.index') }}" class="glass-panel p-5 hover:shadow-md transition">
                    <p class="text-xs text-gray-400 mb-1">Stok Seragam Rendah</p>
                    <p class="text-2xl font-semibold text-gray-800">{{ $lowStockCount }}</p>
                </a>
                <a href="{{ route('outlet.maintenance.index') }}" class="glass-panel p-5 hover:shadow-md transition">
                    <p class="text-xs text-gray-400 mb-1">Pemeliharaan Mendatang</p>
                    <p class="text-2xl font-semibold text-gray-800">{{ $upcomingMaintenanceCount }}</p>
                </a>
            </div>

            <div class="glass-panel p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Jalan Pintas</h3>
                <div class="flex flex-wrap gap-3">
                    @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_REQUESTS))
                        <a href="{{ route('outlet.requests.create') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-ink text-white hover:bg-ink/90 transition">Buat Pengajuan</a>
                    @endif
                    @if (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_RECORDS))
                        <a href="{{ route('outlet.uniforms.records.create') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50 transition">Serah Terima Seragam</a>
                    @endif
                    <a href="{{ route('outlet.assets.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50 transition">Lihat Aset</a>
                    <a href="{{ route('outlet.worklogs.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50 transition">Lihat Work Log</a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
