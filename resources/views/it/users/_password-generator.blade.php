@props(['label' => 'Password'])

{{--
    Widget generate/ketik password manual, dipakai di form Buat User (field
    "password") dan form Reset Password di halaman Edit — password TIDAK
    pernah dibuat/di-generate server-side supaya IT bisa lihat & salin nilai
    persis yang akan disampaikan ke user (WA/Telegram) sebelum submit.
--}}
<div x-data="passwordGenerator()" class="space-y-2">
    <label class="block text-sm font-medium text-gray-700">{{ $label }} <span class="text-red-500">*</span></label>
    <div class="flex gap-2">
        <input :type="visible ? 'text' : 'password'" name="password" x-model="password" required minlength="8"
               placeholder="Ketik manual atau klik &quot;Generate Password Acak&quot;"
               autocomplete="new-password"
               class="flex-1 px-3.5 py-2.5 border border-gray-300 rounded-lg focus:border-gold-500 focus:ring-2 focus:ring-gold-500 font-mono text-sm">
        <button type="button" @click="visible = !visible"
                class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                :aria-label="visible ? 'Sembunyikan password' : 'Lihat password'"
                :title="visible ? 'Sembunyikan password' : 'Lihat password'">
            <svg x-show="!visible" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
            </svg>
            <svg x-show="visible" x-cloak class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18" /><path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" />
                <path d="M9.88 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-1.67 2.68" />
                <path d="M6.61 6.61A13.5 13.5 0 0 0 2 11s3.5 7 10 7a9.1 9.1 0 0 0 4.16-1.02" />
            </svg>
        </button>
        <button type="button" @click="copy()" :disabled="!password"
                class="px-3 py-2 text-xs font-medium border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-40"
                x-text="copied ? 'Tersalin!' : 'Copy'"></button>
    </div>
    <button type="button" @click="generate()"
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gold-100 text-gold-700 hover:bg-gold-200">
        Generate Password Acak
    </button>
    <p class="text-xs text-gray-500">
        Password ini hanya tampil sekali di sini, sampaikan ke user secara manual lewat WA/Telegram
        (email belum aktif). User akan diminta ganti password ini sendiri saat login pertama.
    </p>
</div>

@once
    <script>
        function passwordGenerator(initial = '') {
            return {
                password: initial,
                visible: true,
                copied: false,
                generate() {
                    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
                    const bytes = new Uint32Array(14);
                    crypto.getRandomValues(bytes);
                    this.password = Array.from(bytes, (n) => chars[n % chars.length]).join('');
                    this.visible = true;
                    this.copied = false;
                },
                async copy() {
                    try {
                        await navigator.clipboard.writeText(this.password);
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 1500);
                    } catch (e) {
                        // Clipboard API tidak tersedia (mis. http non-localhost) — abaikan,
                        // IT masih bisa select-all manual dari field yang sudah "visible".
                    }
                },
            };
        }
    </script>
@endonce
