<x-app-layout sidebar="it">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kontrol Modul — Mode Pemeliharaan
        </h2>
    </x-slot>

    <div class="font-it py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <p class="text-sm text-gray-500">
                Tandai halaman tertentu sebagai "Dalam Pemeliharaan" — role selain IT/Admin yang mencoba
                mengakses halaman tersebut akan otomatis diarahkan ke halaman pemberitahuan.
            </p>

            @php
                [$gaModules, $headModules] = $modules->partition(fn ($m) => str_starts_with($m->key, 'ga.'));
            @endphp

            <div class="space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400">General Affairs</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach ($gaModules as $module)
                        @include('it.modules._card', ['module' => $module])
                    @endforeach
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Head</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach ($headModules as $module)
                        @include('it.modules._card', ['module' => $module])
                    @endforeach
                </div>
            </div>

            {{-- Aktivitas terbaru --}}
            <div class="glass-panel overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Aktivitas Mode Pemeliharaan Terbaru</h3>
                </div>
                <div class="px-6 py-4">
                    @forelse ($recentActivity as $log)
                        <div class="relative pl-11 pb-4 last:pb-0 border-l-2 border-gold-200 last:border-transparent">
                            {{-- Ikon dipilih dari $log->action, satu-satunya penanda jenis
                                 aktivitas yang tersedia di data ini (lihat ModuleControlController
                                 -- query recentActivity dibatasi hanya ke dua nilai action ini).
                                 Cabang @else adalah fallback netral kalau suatu saat action lain
                                 ikut masuk ke query ini. --}}
                            @if ($log->action === 'system_module.maintenance_on')
                                <span class="absolute -left-[18px] top-0 w-9 h-9 rounded-full flex items-center justify-center ring-4 ring-white bg-amber-100 text-amber-600">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                                    </svg>
                                </span>
                            @elseif ($log->action === 'system_module.maintenance_off')
                                <span class="absolute -left-[18px] top-0 w-9 h-9 rounded-full flex items-center justify-center ring-4 ring-white bg-emerald-100 text-emerald-600">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2v6" /><path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                                    </svg>
                                </span>
                            @else
                                <span class="absolute -left-[18px] top-0 w-9 h-9 rounded-full flex items-center justify-center ring-4 ring-white bg-gold-100 text-gold-600">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
                                    </svg>
                                </span>
                            @endif
                            <div class="flex items-center justify-between text-sm">
                                <div class="min-w-0">
                                    <p class="text-gray-800 truncate">{{ $log->description }}</p>
                                    <p class="text-xs text-gray-400">oleh {{ $log->user?->name ?? 'Sistem' }}</p>
                                </div>
                                <span class="text-xs text-gray-400 shrink-0">{{ $log->created_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-500 py-4">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
