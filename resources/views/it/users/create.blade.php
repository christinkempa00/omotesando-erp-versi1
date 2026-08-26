<x-app-layout sidebar="it">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('it.users.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat User Baru</h2>
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

                <div class="glass-panel p-6">
                    @include('it.users._contact-fields')
                </div>

                <div class="glass-panel p-6">
                    @include('it.users._password-generator', ['label' => 'Password Awal'])
                </div>

                <div class="glass-panel p-6">
                    @include('it.users._access-fields')
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('it.users.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-gold-500 to-gold-600 text-white text-sm font-medium rounded-lg shadow-[0_4px_12px_-2px_rgba(200,155,44,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(200,155,44,0.5)] transition">Simpan</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
