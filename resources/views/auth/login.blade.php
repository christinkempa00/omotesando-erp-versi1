<x-guest-layout>
    <h2 class="text-ink font-semibold text-lg mb-4">Masuk</h2>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status') ?? (request()->query('expired') ? 'Sesi Anda telah berakhir, silakan login kembali.' : null)" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Username -->
        <div>
            <x-input-label for="email" :value="__('Username')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ showPassword: false }">
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative mt-1">
                <x-text-input id="password"
                                class="block w-full pr-10"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password" />
                <button type="button"
                        @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted hover:text-ink">
                    {{-- ikon mata (show) --}}
                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    {{-- ikon mata dicoret (hide) --}}
                    <svg x-show="showPassword" x-cloak style="display:none" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6a3 3 0 004.2 4.2M9.9 4.6A9.6 9.6 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a17 17 0 01-2.2 3.1M6.1 6.1A17 17 0 002.25 12S6 19.5 12 19.5c1 0 1.9-.13 2.8-.36"/>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-hairline text-action shadow-sm focus:ring-action" name="remember">
                <span class="ms-2 text-sm text-ink-muted">{{ __('Remember me') }}</span>
            </label>
        </div>

        <p class="mt-3 text-xs text-ink-muted">
            Lupa password? <span class="text-action font-medium">Hubungi IT</span> untuk reset.
        </p>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
