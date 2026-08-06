<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Wajib Ganti Password</h2>
        <p class="mt-1 text-sm text-gray-600">
            Ini login pertama Anda (atau password baru saja di-reset IT) — silakan buat
            password baru sebelum melanjutkan.
        </p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        {{-- Sengaja TANPA field password lama — ini password sementara dari
             IT, bukan ganti password sukarela (lihat PasswordController::update()). --}}
        <div>
            <x-input-label for="password" value="Password Baru" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" autofocus />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>Simpan & Lanjutkan</x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
            Log Out
        </button>
    </form>
</x-guest-layout>
