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
                'scm' => 'Supply Chain',
                'purchasing' => 'Purchasing',
                'finance' => 'Finance',
                default => 'General Affairs',
            };
            $sidebarPartial = match ($sidebar) {
                'head' => 'head.partials.sidebar',
                'it' => 'it.partials.sidebar',
                'scm' => 'scm.partials.sidebar',
                'purchasing' => 'purchasing.partials.sidebar',
                'finance' => 'finance.partials.sidebar',
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
                <div class="sticky top-0 z-20 bg-white border-b border-hairline lg:hidden">
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
    </body>
</html>
