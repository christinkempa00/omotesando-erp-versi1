<x-app-layout sidebar="it">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('it.users.index')" parent="Manajemen User" current="Buat User" />
            <h2 class="font-semibold text-xl text-ink leading-tight">Buat User Baru</h2>
        </div>
    </x-slot>

    <div class="font-it py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('it.users.store') }}" class="space-y-6">
                @csrf

                <div class="card-flat p-6">
                    @include('it.users._contact-fields')
                </div>

                <div class="card-flat p-6">
                    @include('it.users._password-generator', ['label' => 'Password Awal'])
                </div>

                <div class="card-flat p-6">
                    @include('it.users._access-fields')
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('it.users.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
