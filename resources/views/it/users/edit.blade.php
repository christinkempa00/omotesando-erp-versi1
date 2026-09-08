<x-app-layout sidebar="it">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('it.users.index')" parent="Manajemen User" current="Edit User" />
            <h2 class="font-semibold text-xl text-ink leading-tight">Edit User {{ $user->name }}</h2>
        </div>
    </x-slot>

    <div class="font-it py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('it.users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="card-flat p-6">
                    @include('it.users._contact-fields')
                </div>

                <div class="card-flat p-6">
                    <h3 class="text-sm font-semibold text-ink mb-3">Status Akun</h3>
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active))
                               class="rounded border-hairline text-action focus:ring-action">
                        Akun aktif (bisa login)
                    </label>
                    <p class="mt-1 text-xs text-ink-muted">
                        User nonaktif tidak bisa login sama sekali, ditolak langsung saat proses login dengan pesan jelas.
                    </p>
                </div>

                <div class="card-flat p-6">
                    @include('it.users._access-fields')
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('it.users.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" class="btn-primary">Simpan Perubahan</button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
