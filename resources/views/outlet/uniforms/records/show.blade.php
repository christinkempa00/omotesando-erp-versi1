<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.uniforms.records.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $record->employee_name }}
                <span class="ml-2 font-mono text-sm text-gray-400">{{ $record->record_code }}</span>
            </h2>
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
                        <dd class="font-medium text-gray-900">{{ $record->summaryLabel() }}</dd>
                    </div>
                    <div><dt class="text-gray-500">Nama Penyerah</dt><dd class="font-medium text-gray-900">{{ $record->issued_by_name ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Serah</dt><dd class="font-medium text-gray-900">{{ $record->issue_date->format('d M Y') }}</dd></div>
                    @if (! $record->isItemized())
                        <div><dt class="text-gray-500">Varian Stok</dt>
                            <dd class="font-medium text-gray-900">
                                @if ($record->uniformStock)
                                    <a href="{{ route('outlet.uniforms.stocks.show', $record->uniformStock) }}" class="text-indigo-600 hover:underline">
                                        {{ $record->uniformStock->stock_code }}
                                    </a>
                                @else — @endif
                            </dd>
                        </div>
                    @endif
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

            @if ($record->isItemized())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-800">Daftar Item</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">No</th>
                                    <th class="px-6 py-3">Nama Barang</th>
                                    <th class="px-6 py-3">Spesifikasi</th>
                                    <th class="px-6 py-3">Qty</th>
                                    <th class="px-6 py-3">Kondisi</th>
                                    <th class="px-6 py-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($record->items as $i => $item)
                                    <tr>
                                        <td class="px-6 py-3 text-gray-500">{{ $i + 1 }}</td>
                                        <td class="px-6 py-3 font-medium text-gray-800">{{ $item->uniform_type }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ $item->specificationLabel() }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ $item->qty }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ $item->item_condition ?: '-' }}</td>
                                        <td class="px-6 py-3 text-gray-600">{{ $item->item_notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

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
            @elseif (auth()->user()->canEdit(\App\Models\UserPagePermission::PAGE_UNIFORMS_RECORDS))
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Tandai Dikembalikan — Pemeriksaan Pengembalian Barang</h3>
                    <form method="POST" action="{{ route('outlet.uniforms.records.return', $record) }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kembali *</label>
                                <input type="date" name="return_date" required value="{{ now()->toDateString() }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penyerah *</label>
                                <input type="text" name="returned_by_name" required value="{{ old('returned_by_name', $record->employee_name) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-400">Karyawan yang mengembalikan barang.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penerima *</label>
                                <input type="text" name="received_by_name" required value="{{ old('received_by_name', Auth::user()->name) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="border border-gray-100 rounded-md divide-y divide-gray-100">
                            <div class="p-3">
                                <x-ya-tidak-radio name="qty_sesuai" label="Jumlah barang sesuai" />
                                <input type="text" name="qty_sesuai_notes" placeholder="Keterangan (opsional)"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="p-3">
                                <x-ya-tidak-radio name="spesifikasi_sesuai" label="Spesifikasi sesuai" />
                                <input type="text" name="spesifikasi_sesuai_notes" placeholder="Keterangan (opsional)"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="p-3">
                                <x-ya-tidak-radio name="kondisi_sesuai" label="Kondisi sesuai" />
                                <input type="text" name="kondisi_sesuai_notes" placeholder="Keterangan (opsional)"
                                       class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-signature-pad name="return_signature" label="Tanda Tangan Penyerah (Karyawan)" :required="false" />
                            <x-signature-pad name="received_by_signature" label="Tanda Tangan Penerima" :required="false" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Fisik Barang *</label>
                            <select name="return_condition" required class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (\App\Models\GA\UniformRecord::conditionLabels() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="return_notes" rows="2" placeholder="Catatan pengembalian (opsional)"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                        </div>

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
