<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.assets.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $asset->name }} <span class="text-gray-400 font-normal">({{ $asset->asset_code }})</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm rounded-lg p-6 space-y-6">
                <div class="grid grid-cols-2 gap-4 sm:max-w-md">
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Foto Aset</p>
                        @if ($asset->image_path)
                            <img src="{{ Storage::url($asset->image_path) }}" class="w-full aspect-square rounded-lg object-cover">
                        @else
                            <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs text-center px-2">
                                Belum ada foto
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">Foto SN</p>
                        @if ($asset->serial_number_photo_path)
                            <img src="{{ Storage::url($asset->serial_number_photo_path) }}" class="w-full aspect-square rounded-lg object-cover">
                        @else
                            <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs text-center px-2">
                                Belum ada foto
                            </div>
                        @endif
                    </div>
                </div>

                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
                    {{ $statusLabels[$asset->status] }}
                </span>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-t border-gray-100 pt-6">
                    <div><dt class="text-gray-500">Merk</dt><dd class="font-medium text-gray-900">{{ $asset->brand ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">Nomor Serial</dt><dd class="font-medium text-gray-900">{{ $asset->serial_number ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">Lokasi</dt><dd class="font-medium text-gray-900">{{ $asset->location ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">Penanggung Jawab</dt><dd class="font-medium text-gray-900">{{ $asset->custodian_name ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">Jumlah</dt><dd class="font-medium text-gray-900">{{ $asset->quantity }}</dd></div>
                    <div><dt class="text-gray-500">Tanggal Beli</dt><dd class="font-medium text-gray-900">{{ optional($asset->purchase_date)->format('d/m/y') ?: '-' }}</dd></div>
                    @if ($asset->notes)
                        <div class="sm:col-span-2"><dt class="text-gray-500">Catatan</dt><dd class="font-medium text-gray-900">{{ $asset->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            <div>
                <a href="{{ route('outlet.assets.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Kembali ke daftar aset</a>
            </div>
        </div>
    </div>
</x-app-layout>
