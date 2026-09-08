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
        <label class="block text-xs font-medium text-ink-muted mb-1">Nama Area *</label>
        <input type="text" name="name" required value="{{ old('name', $area?->name) }}" placeholder="mis. Dapur, Toilet, Area Makan"
               class="w-full field-input text-sm">
    </div>

    <div class="flex gap-2 w-full sm:w-auto">
        <button type="submit" class="flex-1 sm:flex-none btn-primary">
            {{ $area ? 'Simpan Perubahan' : 'Tambah Area' }}
        </button>
        @if ($area)
            <a href="{{ route('ga.outlet-areas.manage', $branch) }}" class="flex-1 sm:flex-none btn-secondary">Batal</a>
        @endif
    </div>
</form>
