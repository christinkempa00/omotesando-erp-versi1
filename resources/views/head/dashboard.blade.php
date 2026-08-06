<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Statistik Per Modul — breakdown status dalam bentuk donut chart,
                 lebih mudah dibaca sekilas ketimbang angka polos. Klik kartu
                 utk lanjut ke halaman modulnya. Approval & daftar pengajuan
                 ada di halaman tersendiri (Approval Inbox / Semua Pengajuan)
                 supaya Dashboard murni jadi overview. --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Statistik Per Modul</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-stretch">
                    <a href="{{ route('head.approvals.index') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-col hover:shadow-md transition">
                        <p class="text-sm font-medium text-gray-700 mb-3">Request</p>
                        <div class="mt-auto">
                            <x-donut-chart :data="$moduleCharts['requests']['data']" :labels="$moduleCharts['requests']['labels']"
                                            :colors="$moduleCharts['requests']['colors']" :total="$moduleCharts['requests']['total']" size="w-20 h-20" />
                            <p class="text-xs text-gray-400 mt-2">{{ $moduleSummary['requests']['pending'] }} pending &middot; {{ $moduleSummary['requests']['total'] }} total</p>
                        </div>
                    </a>
                    <a href="{{ route('head.assets.index') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-col hover:shadow-md transition">
                        <p class="text-sm font-medium text-gray-700 mb-3">Inventaris Aset</p>
                        <div class="mt-auto">
                            <x-donut-chart :data="$moduleCharts['assets']['data']" :labels="$moduleCharts['assets']['labels']"
                                            :colors="$moduleCharts['assets']['colors']" :total="$moduleCharts['assets']['total']" size="w-20 h-20" />
                            <p class="text-xs text-gray-400 mt-2">{{ $moduleSummary['assets']['bagus'] }} bagus &middot; {{ $moduleSummary['assets']['total'] }} total</p>
                        </div>
                    </a>
                    <a href="{{ route('head.uniforms.index') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-col hover:shadow-md transition">
                        <p class="text-sm font-medium text-gray-700 mb-3">Inventaris Seragam</p>
                        <div class="mt-auto">
                            <x-donut-chart :data="$moduleCharts['uniforms']['data']" :labels="$moduleCharts['uniforms']['labels']"
                                            :colors="$moduleCharts['uniforms']['colors']" :total="$moduleCharts['uniforms']['total']" size="w-20 h-20" />
                            <p class="text-xs text-gray-400 mt-2">{{ $moduleSummary['uniforms']['total_stock'] }} stok &middot; {{ $moduleSummary['uniforms']['low_stock'] }} low stock</p>
                        </div>
                    </a>
                    <a href="{{ route('head.maintenance.index') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-col hover:shadow-md transition">
                        <p class="text-sm font-medium text-gray-700 mb-3">Jadwal Pemeliharaan</p>
                        <div class="mt-auto">
                            <x-donut-chart :data="$moduleCharts['maintenance']['data']" :labels="$moduleCharts['maintenance']['labels']"
                                            :colors="$moduleCharts['maintenance']['colors']" :total="$moduleCharts['maintenance']['total']" size="w-20 h-20" />
                            <p class="text-xs text-gray-400 mt-2">{{ $moduleSummary['maintenance']['active'] }} aktif &middot; {{ $moduleSummary['maintenance']['completed'] }} selesai</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Biaya — pemeliharaan per bulan (bar chart) & total biaya request GA --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Biaya</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
                    <a href="{{ route('head.maintenance.index') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-col hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-sm font-medium text-gray-700">Biaya Pemeliharaan per Bulan</p>
                            <p class="text-sm font-bold text-gray-800">Rp {{ number_format($totalMaintenanceCost, 0, ',', '.') }}</p>
                        </div>
                        <x-bar-chart :data="$maintenanceCostByMonth" color="#3b82f6" />
                    </a>
                    <a href="{{ route('head.requests.index') }}" class="bg-white shadow-sm rounded-lg p-4 flex flex-col hover:shadow-md transition">
                        <p class="text-sm font-medium text-gray-700 mb-3">Total Biaya Request GA</p>
                        <p class="text-2xl font-bold text-gray-800 mt-auto">Rp {{ number_format($totalRequestCost, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-2">Akumulasi seluruh pengajuan GA (semua status)</p>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
