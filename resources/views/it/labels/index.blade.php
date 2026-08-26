<x-app-layout sidebar="it">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Kelola Label
            </h2>
            <a href="{{ route('it.board.index') }}" class="text-sm text-gold-600 hover:text-gold-700 font-medium">
                &larr; Kembali ke Papan Kerja
            </a>
        </div>
    </x-slot>

    @php
        $colorClasses = [
            'red' => 'bg-red-100 text-red-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'yellow' => 'bg-yellow-100 text-yellow-700',
            'green' => 'bg-green-100 text-green-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'purple' => 'bg-purple-100 text-purple-700',
            'pink' => 'bg-pink-100 text-pink-700',
            'gray' => 'bg-gray-100 text-gray-700',
        ];
    @endphp

    <div class="font-it py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="glass-panel p-5">
                <h3 class="font-medium text-gray-800 mb-3">Tambah Label Baru</h3>
                <form method="POST" action="{{ route('it.labels.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nama Label</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="50"
                               class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
                    </div>
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Warna</label>
                        <select name="color" required class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
                            @foreach ($colors as $color)
                                <option value="{{ $color }}">{{ ucfirst($color) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium rounded-lg bg-gradient-to-r from-gold-500 to-gold-600 text-white shadow-[0_4px_12px_-2px_rgba(200,155,44,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(200,155,44,0.5)] transition">
                        Tambah
                    </button>
                </form>
            </div>

            <div class="glass-panel overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Label Tersedia</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($labels as $label)
                        <div class="px-6 py-3 flex items-center justify-between text-sm">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $colorClasses[$label->color] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $label->name }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $label->tasks_count }} task</span>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-gray-400 italic">Belum ada label.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
