<x-guest-layout>
    <div class="text-center mb-4">
        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ \App\Models\GA\Asset::statusBadgeColor($asset->status) }}">
            {{ $statusLabels[$asset->status] ?? $asset->status }}
        </span>
    </div>

    @if ($asset->image_path)
        <img src="{{ Storage::url($asset->image_path) }}" class="w-full aspect-square rounded-lg object-cover mb-4">
    @else
        <div class="w-full aspect-square rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-sm mb-4">
            Belum ada foto
        </div>
    @endif

    <h1 class="text-lg font-bold text-gray-900 text-center">{{ $asset->name }}</h1>
    <p class="text-sm font-mono text-gray-500 text-center mb-4">{{ $asset->asset_code }}</p>

    <dl class="grid grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-4">
        <div>
            <dt class="text-gray-400 text-xs">Kategori</dt>
            <dd class="font-medium text-gray-800">{{ $asset->category ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Brand / Model</dt>
            <dd class="font-medium text-gray-800">{{ $asset->brand ?: '-' }} {{ $asset->model }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Serial Number</dt>
            <dd class="font-medium text-gray-800">{{ $asset->serial_number ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Lokasi</dt>
            <dd class="font-medium text-gray-800">{{ $asset->location ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">Outlet</dt>
            <dd class="font-medium text-gray-800">{{ $asset->branch?->name ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-400 text-xs">PIC</dt>
            <dd class="font-medium text-gray-800">{{ $asset->custodian_name ?: '-' }}</dd>
        </div>
    </dl>

    <p class="text-center text-xs text-gray-400 mt-6">
        Tampilan publik &middot; hanya-baca &middot; {{ config('app.name') }}
    </p>
</x-guest-layout>
