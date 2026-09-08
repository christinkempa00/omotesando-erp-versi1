<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.maintenance.index')" parent="Jadwal Pemeliharaan" current="Buat Pemeliharaan" />
            <h2 class="font-semibold text-xl text-ink leading-tight">
                Jadwalkan Pemeliharaan Baru
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="card-flat p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ga.maintenance.store') }}">
                    @csrf
                    @include('ga.maintenance._form')

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('ga.maintenance.index') }}"
                           class="btn-secondary">Batal</a>
                        <button type="submit"
                                class="inline-flex items-center btn-primary">
                            Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
