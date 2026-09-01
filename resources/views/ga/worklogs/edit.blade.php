<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.worklogs.show', $workLog)" />
            <h2 class="font-semibold text-xl text-ink leading-tight">Edit Work Log</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Foto yang sudah ada + hapus per-foto — SENGAJA di luar form
                 update di bawah (bukan nested form), lihat komentar di
                 _form.blade.php. --}}
            @if ($workLog->attachments->isNotEmpty())
                <div class="glass-panel p-6 mb-4">
                    <p class="text-xs font-medium text-gray-500 mb-2">Foto yang sudah ada</p>
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
                        @foreach ($workLog->attachments as $attachment)
                            <div class="relative group">
                                <img src="{{ Storage::url($attachment->photo_path) }}" class="w-full aspect-square rounded-md object-cover border border-gray-200">
                                <form method="POST" action="{{ route('ga.worklogs.attachments.destroy', [$workLog, $attachment]) }}"
                                      onsubmit="return confirm('Hapus foto ini?')" class="absolute top-1 right-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus foto"
                                            class="w-5 h-5 flex items-center justify-center rounded-full bg-black/60 text-white text-xs hover:bg-red-600">
                                        &times;
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('ga.worklogs.update', $workLog) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('ga.worklogs._form')

                <div class="flex justify-end gap-3 mt-6">
                    <a href="{{ route('ga.worklogs.show', $workLog) }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                    <button type="submit" class="btn-gold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
