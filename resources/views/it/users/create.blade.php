<x-app-layout sidebar="it">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('it.users.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat User Baru</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('it.users.store') }}" class="space-y-6">
                @csrf

                <div class="bg-white shadow-sm rounded-lg p-6">
                    @include('it.users._contact-fields')
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    @include('it.users._password-generator', ['label' => 'Password Awal'])
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    @include('it.users._access-fields')
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('it.users.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
