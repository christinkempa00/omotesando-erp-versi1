<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.uniforms.records.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $record->employee_name }}
                    <span class="ml-2 font-mono text-sm text-gray-400">{{ $record->record_code }}</span>
                </h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('ga.uniforms.records.document', $record) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">Dokumen Serah Terima</a>
                <a href="{{ route('ga.uniforms.records.edit', $record) }}"
                   class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">Edit</a>
                <form method="POST" action="{{ route('ga.uniforms.records.destroy', $record) }}"
                      onsubmit="return confirm('Hapus catatan serah-terima ini? Stok TIDAK akan otomatis dikembalikan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-md hover:bg-red-100">Hapus</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium {{ \App\Models\GA\UniformRecord::statusBadgeColor($record->status) }}">
                        {{ $statusLabels[$record->status] }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Seragam</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $record->uniform_type }}
                            @if ($record->size) &middot; {{ $record->size }} @endif
                            @if ($record->color) &middot; {{ $record->color }} @endif
                        </dd>
                    </div>
                    <div><dt class="text-gray-500">Outlet</dt><dd class="font-medium text-gray-900">{{ $record->branch->name }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Serah</dt><dd class="font-medium text-gray-900">{{ $record->issue_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-gray-500">Varian Stok</dt>
                        <dd class="font-medium text-gray-900">
                            @if ($record->uniformStock)
                                <a href="{{ route('ga.uniforms.stocks.show', $record->uniformStock) }}" class="text-indigo-600 hover:underline">
                                    {{ $record->uniformStock->stock_code }}
                                </a>
                            @else — @endif
                        </dd>
                    </div>
                </dl>

                @if ($record->issue_notes)
                    <div class="mt-4 text-sm">
                        <p class="text-gray-500">Catatan Serah</p>
                        <p class="text-gray-800 whitespace-pre-line">{{ $record->issue_notes }}</p>
                    </div>
                @endif

                <div class="mt-4 flex gap-4">
                    @if ($record->issue_photo_path)
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Foto Serah</p>
                            <img src="{{ Storage::url($record->issue_photo_path) }}" class="w-32 h-32 rounded object-cover">
                        </div>
                    @endif
                    @if ($record->signature_path)
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Tanda Tangan</p>
                            <img src="{{ Storage::url($record->signature_path) }}" class="w-32 h-32 rounded object-contain bg-gray-50">
                        </div>
                    @endif
                </div>
            </div>

            @if ($record->status === \App\Models\GA\UniformRecord::STATUS_RETURNED)
                <div class="bg-green-50 border border-green-200 shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-green-800 mb-1">Sudah Dikembalikan</h3>
                    <p class="text-sm text-green-700">
                        {{ optional($record->return_date)->format('d M Y') }} —
                        kondisi: {{ $conditionLabels[$record->return_condition] ?? $record->return_condition }}
                    </p>
                    @if ($record->return_notes)
                        <p class="mt-2 text-sm text-green-800 whitespace-pre-line">{{ $record->return_notes }}</p>
                    @endif
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tandai Dikembalikan</h3>
                    <form method="POST" action="{{ route('ga.uniforms.records.return', $record) }}" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kembali *</label>
                                <input type="date" name="return_date" required value="{{ now()->toDateString() }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi *</label>
                                <select name="return_condition" required class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach (\App\Models\GA\UniformRecord::conditionLabels() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <textarea name="return_notes" rows="2" placeholder="Catatan pengembalian (opsional)"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                        <p class="text-xs text-gray-400">
                            Kondisi "Baik" mengembalikan stok ke tersedia, "Rusak" masuk stok rusak, "Hilang" tidak mengubah stok.
                        </p>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                            Tandai Dikembalikan
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
