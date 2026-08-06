<x-app-layout sidebar="head">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kontrol Modul
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ($modules as $module)
                    <div class="bg-white shadow-sm rounded-lg p-5">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $module->label }}</h3>
                                @if ($module->description)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $module->description }}</p>
                                @endif
                            </div>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $module->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                                {{ $module->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('head.modules.toggle', $module) }}"
                              onsubmit="return confirm('{{ $module->is_active ? 'Nonaktifkan' : 'Aktifkan' }} modul {{ $module->label }}?');">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-2 text-sm font-medium rounded-md
                                        {{ $module->is_active
                                            ? 'bg-red-50 text-red-600 hover:bg-red-100'
                                            : 'bg-green-600 text-white hover:bg-green-700' }}">
                                {{ $module->is_active ? 'Nonaktifkan Modul' : 'Aktifkan Modul' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{-- Aktivitas terbaru --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Aktivitas Kontrol Modul Terbaru</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentActivity as $log)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <div class="min-w-0">
                                <p class="text-gray-800 truncate">{{ $log->description }}</p>
                                <p class="text-xs text-gray-400">oleh {{ $log->user?->name ?? 'Sistem' }}</p>
                            </div>
                            <span class="text-xs text-gray-400 shrink-0">{{ $log->created_at->format('d M Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-gray-500">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
