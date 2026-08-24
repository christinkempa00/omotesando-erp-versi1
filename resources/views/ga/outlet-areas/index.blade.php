<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Area Pemeriksaan</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">{{ session('success') }}</div>
            @endif

            <p class="text-sm text-gray-500">Pilih outlet untuk mengelola daftar area pemeriksaannya.</p>

            <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-100">
                @foreach ($branches as $branch)
                    <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 cursor-pointer"
                         onclick="window.location='{{ route('ga.outlet-areas.manage', $branch) }}'">
                        <span class="font-medium text-gray-800">{{ $branch->name }}</span>
                        <span class="text-sm text-gray-500">{{ $activeCounts[$branch->id] ?? 0 }} area aktif</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
