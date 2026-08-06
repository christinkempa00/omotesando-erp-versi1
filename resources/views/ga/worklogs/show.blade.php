<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.worklogs.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                    Work Log — {{ optional($workLog->work_date)->format('d/m/Y') }}
                    @if ($workLog->isComplete())
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Selesai</span>
                    @else
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Belum Ada Hasil</span>
                    @endif
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('ga.worklogs.edit', $workLog) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                    Edit
                </a>
                <form method="POST" action="{{ route('ga.worklogs.destroy', $workLog) }}"
                      onsubmit="return confirm('Yakin hapus work log ini? Aksi ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-md hover:bg-red-100">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6 space-y-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <div class="w-full sm:w-40 shrink-0">
                        <p class="text-xs font-medium text-gray-500 mb-1">Lampiran</p>
                        @if ($workLog->attachments->isNotEmpty())
                            <div class="grid grid-cols-4 sm:grid-cols-2 gap-2">
                                @foreach ($workLog->attachments as $attachment)
                                    <a href="{{ Storage::url($attachment->photo_path) }}" target="_blank">
                                        <img src="{{ Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-lg object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="w-full sm:w-40 aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs text-center px-2">
                                Belum ada foto
                            </div>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm flex-1">
                        <div><dt class="text-gray-500">Tanggal Pengerjaan</dt><dd class="font-medium text-gray-900">{{ optional($workLog->work_date)->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-gray-500">Waktu Pengerjaan</dt><dd class="font-medium text-gray-900">{{ substr($workLog->work_time, 0, 5) }}</dd></div>
                        <div><dt class="text-gray-500">Kategori Pengerjaan</dt><dd class="font-medium text-gray-900">{{ $categoryLabels[$workLog->category] ?? $workLog->category }}</dd></div>
                        <div><dt class="text-gray-500">Outlet</dt><dd class="font-medium text-gray-900">{{ $workLog->branch->name }}</dd></div>
                        <div><dt class="text-gray-500">Teknisi in Charge</dt><dd class="font-medium text-gray-900">{{ $workLog->technician_in_charge }}</dd></div>
                        <div><dt class="text-gray-500">Teknisi Assist</dt><dd class="font-medium text-gray-900">{{ $workLog->technician_assist ?: '-' }}</dd></div>
                    </dl>
                </div>

                <dl class="space-y-4 text-sm border-t border-gray-100 pt-6">
                    <div>
                        <dt class="text-gray-500 mb-1">Detail Pengerjaan</dt>
                        <dd class="font-medium text-gray-900 whitespace-pre-line">{{ $workLog->work_detail }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-1">Hasil Pengerjaan</dt>
                        <dd class="font-medium text-gray-900 whitespace-pre-line">{{ $workLog->work_result ?: 'Belum diisi — pekerjaan belum ditandai selesai.' }}</dd>
                    </div>
                    @if ($workLog->notes)
                        <div>
                            <dt class="text-gray-500 mb-1">Keterangan</dt>
                            <dd class="font-medium text-gray-900 whitespace-pre-line">{{ $workLog->notes }}</dd>
                        </div>
                    @endif
                </dl>

                <p class="text-xs text-gray-400 border-t border-gray-100 pt-4">
                    Dicatat oleh {{ $workLog->createdBy?->name ?? '-' }} pada {{ $workLog->created_at->format('d/m/Y H:i') }}
                </p>
            </div>

            <div>
                <a href="{{ route('ga.worklogs.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Kembali ke daftar Work Log</a>
            </div>
        </div>
    </div>
</x-app-layout>
