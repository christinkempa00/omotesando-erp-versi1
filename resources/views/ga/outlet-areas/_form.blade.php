@php
    $area = $area ?? null;
@endphp

<form method="POST" action="{{ $area ? route('ga.outlet-areas.update', [$branch, $area]) : route('ga.outlet-areas.store', $branch) }}"
      class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3">
    @csrf
    @if ($area)
        @method('PUT')
    @endif
    <input type="hidden" name="branch_id" value="{{ $branch->id }}">

    <div class="flex-1 sm:min-w-[10rem]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Nama Area *</label>
        <input type="text" name="name" required value="{{ old('name', $area?->name) }}" placeholder="mis. Dapur, Toilet, Area Makan"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500 text-sm">
    </div>

    <div class="flex gap-2 w-full sm:w-auto">
        <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-gold-500 text-white text-sm font-medium rounded-md hover:bg-gold-600">
            {{ $area ? 'Simpan Perubahan' : 'Tambah Area' }}
        </button>
        @if ($area)
            <a href="{{ route('ga.outlet-areas.manage', $branch) }}" class="flex-1 sm:flex-none text-center px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-200 sm:border-0 rounded-md">Batal</a>
        @endif
    </div>
</form>
