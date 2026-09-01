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
                class="px-3 py-2 text-xs font-medium border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                x-text="visible ? 'Sembunyikan' : 'Lihat'"></button>
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
