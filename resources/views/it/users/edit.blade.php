<x-app-layout sidebar="it">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('it.users.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit User — {{ $user->name }}</h2>
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

                <div class="glass-panel p-6">
                    @include('it.users._contact-fields')
                </div>

                <div class="glass-panel p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Status Akun</h3>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active))
                               class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                        Akun aktif (bisa login)
                    </label>
                    <p class="mt-1 text-xs text-gray-400">
                        User nonaktif tidak bisa login sama sekali — ditolak langsung saat proses login dengan pesan jelas.
                    </p>
                </div>

                <div class="glass-panel p-6">
                    @include('it.users._access-fields')
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('it.users.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-gold-500 to-gold-600 text-white text-sm font-medium rounded-lg shadow-[0_4px_12px_-2px_rgba(200,155,44,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(200,155,44,0.5)] transition">Simpan Perubahan</button>
                </div>
            </form>

            {{-- Form terpisah (BUKAN nested di dalam form update di atas) —
                 reset password adalah aksi independen dari data profil user. --}}
            <div class="glass-panel p-6 border-amber-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Reset Password</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Set password baru untuk user ini — user akan wajib ganti password sendiri lagi saat login berikutnya.
                </p>
                <form method="POST" action="{{ route('it.users.reset-password', $user) }}" class="space-y-4">
                    @csrf
                    @include('it.users._password-generator', ['label' => 'Password Baru'])
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-sm font-medium rounded-lg shadow-[0_4px_12px_-2px_rgba(245,158,11,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(245,158,11,0.5)] transition">
                            Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
