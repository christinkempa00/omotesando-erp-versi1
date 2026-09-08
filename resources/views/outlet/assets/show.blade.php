<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.assets.index')" parent="Aset" current="Detail Aset" />
            <h2 class="font-semibold text-xl text-ink leading-tight">
                {{ $asset->name }} <span class="text-ink-muted font-normal">({{ $asset->asset_code }})</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="card-flat p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4 sm:max-w-md">
                    <div>
                        <p class="text-xs font-medium text-ink-muted mb-1">Foto Aset</p>
                        @if ($asset->image_path)
                            <img src="{{ Storage::url($asset->image_path) }}" class="w-full aspect-square rounded-lg object-cover">
                        @else
                            <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-ink-muted text-xs text-center px-2">
                                Belum ada foto
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-ink-muted mb-1">Foto SN</p>
                        @if ($asset->serial_number_photo_path)
                            <img src="{{ Storage::url($asset->serial_number_photo_path) }}" class="w-full aspect-square rounded-lg object-cover">
                        @else
                            <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-ink-muted text-xs text-center px-2">
                                Belum ada foto
                            </div>
                        @endif
                    </div>
                </div>

                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
                    {{ $statusLabels[$asset->status] }}
                </span>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-t border-hairline pt-6">
                    <div><dt class="text-ink-muted">Merk</dt><dd class="font-medium text-ink">{{ $asset->brand ?: '-' }}</dd></div>
                    <div><dt class="text-ink-muted">Nomor Serial</dt><dd class="font-medium text-ink">{{ $asset->serial_number ?: '-' }}</dd></div>
                    <div><dt class="text-ink-muted">Lokasi</dt><dd class="font-medium text-ink">{{ $asset->location ?: '-' }}</dd></div>
                    <div><dt class="text-ink-muted">Penanggung Jawab</dt><dd class="font-medium text-ink">{{ $asset->custodian_name ?: '-' }}</dd></div>
                    <div><dt class="text-ink-muted">Jumlah</dt><dd class="font-medium text-ink">{{ $asset->quantity }}</dd></div>
                    <div><dt class="text-ink-muted">Tanggal Beli</dt><dd class="font-medium text-ink">{{ optional($asset->purchase_date)->format('d/m/y') ?: '-' }}</dd></div>
                    @if ($asset->notes)
                        <div class="sm:col-span-2"><dt class="text-ink-muted">Catatan</dt><dd class="font-medium text-ink">{{ $asset->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            <div>
                <a href="{{ route('outlet.assets.index') }}" class="text-sm text-action hover:text-action-hover">&larr; Kembali ke daftar aset</a>
            </div>
        </div>
    </div>
</x-app-layout>
