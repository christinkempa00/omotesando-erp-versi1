<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <x-back-link :href="route('ga.worklogs.index')" parent="Work Log" current="Detail Work Log" />
                <h2 class="font-semibold text-xl text-ink leading-tight flex items-center gap-2">
                    Work Log tanggal {{ optional($workLog->work_date)->format('d/m/Y') }}
                    @if ($workLog->isComplete())
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Selesai</span>
                    @else
                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Belum Ada Hasil</span>
                    @endif
                </h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('ga.worklogs.edit', $workLog) }}"
                   class="btn-secondary">
                    Edit
                </a>
                <form method="POST" action="{{ route('ga.worklogs.destroy', $workLog) }}"
                      onsubmit="return confirm('Yakin hapus work log ini? Aksi ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">
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

            <div class="card-flat p-6 space-y-6">
                <div class="flex flex-col sm:flex-row gap-6">
                    <div class="w-full sm:w-40 shrink-0">
                        <p class="text-xs font-medium text-ink-muted mb-1">Lampiran</p>
                        @if ($workLog->attachments->isNotEmpty())
                            <div class="grid grid-cols-4 sm:grid-cols-2 gap-2">
                                @foreach ($workLog->attachments as $attachment)
                                    <a href="{{ Storage::url($attachment->photo_path) }}" target="_blank">
                                        <img src="{{ Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-lg object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="w-full sm:w-40 aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-ink-muted text-xs text-center px-2">
                                Belum ada foto
                            </div>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm flex-1">
                        <div><dt class="text-ink-muted">Tanggal Pengerjaan</dt><dd class="font-medium text-ink">{{ optional($workLog->work_date)->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-ink-muted">Waktu Mulai</dt><dd class="font-medium text-ink">{{ substr($workLog->start_time, 0, 5) }}</dd></div>
                        <div><dt class="text-ink-muted">Waktu Berakhir</dt><dd class="font-medium text-ink">{{ $workLog->end_time ? substr($workLog->end_time, 0, 5) : '-' }}</dd></div>
                        <div><dt class="text-ink-muted">Durasi</dt><dd class="font-medium text-ink">{{ $workLog->durationLabel() ?? '-' }}</dd></div>
                        <div><dt class="text-ink-muted">Kategori Pengerjaan</dt><dd class="font-medium text-ink">{{ $categoryLabels[$workLog->category] ?? $workLog->category }}</dd></div>
                        <div><dt class="text-ink-muted">Outlet</dt><dd class="font-medium text-ink">{{ $workLog->outletLabel() }}</dd></div>
                        <div><dt class="text-ink-muted">Teknisi in Charge</dt><dd class="font-medium text-ink">{{ $workLog->technician_in_charge }}</dd></div>
                        <div><dt class="text-ink-muted">Teknisi Assist</dt><dd class="font-medium text-ink">{{ $workLog->technician_assist ?: '-' }}</dd></div>
                    </dl>
                </div>

                <dl class="space-y-4 text-sm border-t border-hairline pt-6">
                    <div>
                        <dt class="text-ink-muted mb-1">Detail Pengerjaan</dt>
                        <dd class="font-medium text-ink whitespace-pre-line">{{ $workLog->work_detail }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted mb-1">Hasil Pengerjaan</dt>
                        <dd class="font-medium text-ink whitespace-pre-line">{{ $workLog->work_result ?: 'Belum diisi karena pekerjaan belum ditandai selesai.' }}</dd>
                    </div>
                    @if ($workLog->notes)
                        <div>
                            <dt class="text-ink-muted mb-1">Keterangan</dt>
                            <dd class="font-medium text-ink whitespace-pre-line">{{ $workLog->notes }}</dd>
                        </div>
                    @endif
                </dl>

                <p class="text-xs text-ink-muted border-t border-hairline pt-4">
                    Dicatat oleh {{ $workLog->createdBy?->name ?? '-' }} pada {{ $workLog->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
