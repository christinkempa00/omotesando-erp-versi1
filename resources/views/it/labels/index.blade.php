<x-app-layout sidebar="it">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Kelola Label
            </h2>
            <a href="{{ route('it.board.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
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

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="font-medium text-gray-800 mb-3">Tambah Label Baru</h3>
                <form method="POST" action="{{ route('it.labels.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nama Label</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="50"
                               class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Warna</label>
                        <select name="color" required class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($colors as $color)
                                <option value="{{ $color }}">{{ ucfirst($color) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        Tambah
                    </button>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
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
                        <p class="px-6 py-8 text-center text-sm text-gray-500">Belum ada label.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
