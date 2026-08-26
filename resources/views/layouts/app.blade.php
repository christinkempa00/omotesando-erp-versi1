<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php
            $moduleLabel = match ($sidebar) {
                'head' => 'Head Office',
                'it' => 'IT Support',
                default => 'General Affairs',
            };
            $sidebarPartial = match ($sidebar) {
                'head' => 'head.partials.sidebar',
                'it' => 'it.partials.sidebar',
                default => 'layouts.sidebar',
            };
        @endphp
        <div x-data="{ sidebarOpen: false }" class="min-h-screen flex bg-surface">
            <!-- Mobile sidebar overlay -->
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-ink/50 lg:hidden"
                 style="display: none;"></div>

            @include($sidebarPartial)

            <div class="flex-1 flex flex-col min-w-0">
                <!-- Topbar -->
                <div class="sticky top-0 z-20 {{ $sidebar === 'it' ? 'bg-white/80 backdrop-blur-md border-b border-gold-500/20' : 'bg-white border-b border-hairline' }} lg:hidden">
                    <div class="flex items-center px-4 py-3">
                        <button @click="sidebarOpen = true" class="text-ink-muted hover:text-ink">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <span class="ml-3 font-semibold text-ink text-sm">Allez Group <span class="font-normal text-ink-muted">· {{ $moduleLabel }}</span></span>
                    </div>
                </div>

                {{-- Banner utk IT/Admin yang tetap lolos ke halaman yang sedang
                     ditandai dalam pemeliharaan (lihat CheckModuleMaintenance) --}}
                @isset($maintenanceBannerModule)
                    <div class="bg-amber-50 border-b border-amber-200 px-4 py-2.5 text-sm text-amber-800 flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v4M12 17h.01" /><path d="M10.29 3.86 1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0Z" />
                        </svg>
                        <span>
                            <strong>{{ $maintenanceBannerModule->name }}</strong> sedang dalam mode pemeliharaan untuk role lain — Anda tetap bisa mengakses karena role IT/Admin.
                        </span>
                    </div>
                @endisset

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-b border-hairline">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Popup "Dalam Pemeliharaan" — muncul di halaman tujuan redirect
             CheckModuleMaintenance (lihat MaintenanceNoticeController), bukan
             halaman penuh terpisah lagi. --}}
        @if (session('maintenanceNotice'))
            @php $maintenanceNotice = session('maintenanceNotice'); @endphp
            <div x-data="{ open: true }" x-show="open"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4">
                <div @click.outside="open = false" class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 text-center">
                    <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-semibold text-gray-800">Modul Sedang Dalam Pemeliharaan</h2>
                    <p class="mt-2 text-sm text-gray-500">
                        Halaman <strong>{{ $maintenanceNotice['name'] }}</strong> sedang tidak dapat diakses karena sedang dalam mode pemeliharaan.
                    </p>

                    @if ($maintenanceNotice['note'])
                        <div class="mt-4 rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800 text-left">
                            {{ $maintenanceNotice['note'] }}
                        </div>
                    @endif

                    <button @click="open = false" type="button"
                            class="mt-6 px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                        Tutup
                    </button>
                </div>
            </div>
        @endif
    </body>
</html>
