<x-app-layout :sidebar="$sidebar">
    <div class="py-16">
        <div class="max-w-xl mx-auto px-4 text-center">
            <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.5 6.5a4 4 0 0 0-5.6 4.9L4 16.3V20h3.7l4.9-4.9a4 4 0 0 0 4.9-5.6l-2.6 2.6-2-2 2.6-2.6Z" />
                </svg>
            </div>

            <h1 class="text-xl font-semibold text-gray-800">Modul Sedang Dalam Pemeliharaan</h1>
            <p class="mt-2 text-sm text-gray-500">
                Halaman <strong>{{ $module->name }}</strong> sedang tidak dapat diakses karena sedang dalam mode pemeliharaan.
            </p>

            @if ($module->maintenance_note)
                <div class="mt-6 rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 text-left">
                    {{ $module->maintenance_note }}
                </div>
            @endif

            <a href="{{ route($backRoute) }}"
               class="mt-8 inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 6 9 12l6 6" />
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</x-app-layout>
