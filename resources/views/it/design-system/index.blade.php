<x-app-layout sidebar="it">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Design System — Modul IT
        </h2>
    </x-slot>

    <div class="font-it py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <p class="text-sm text-gray-500 leading-relaxed max-w-2xl">
                Referensi visual "gold/glass" milik modul IT sendiri (Kontrol Modul, Papan Kerja,
                Manajemen User) — meniru tampilan sistem PHP native IT. Ini <strong>berbeda dari</strong>
                sistem ink/accent/Plus Jakarta Sans yang dipakai GA, SCM, Purchasing, Finance, dan Head
                (lihat <code class="text-xs">.design-sync/NOTES.md</code> untuk itu) — kedua sistem sengaja
                dipisah per modul, bukan salah satu yang lebih baru/lama.
            </p>

            {{-- Warna --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">Palet Gold</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach ([
                        ['name' => 'Gold 100', 'class' => 'bg-gold-100', 'token' => 'bg-gold-100'],
                        ['name' => 'Gold 300', 'class' => 'bg-gold-300', 'token' => 'bg-gold-300'],
                        ['name' => 'Gold 500', 'class' => 'bg-gold-500', 'token' => 'bg-gold-500'],
                        ['name' => 'Gold 700', 'class' => 'bg-gold-700', 'token' => 'bg-gold-700'],
                        ['name' => 'Gold 950', 'class' => 'bg-gold-950', 'token' => 'bg-gold-950'],
                    ] as $c)
                        <div class="glass-panel overflow-hidden">
                            <div class="h-16 {{ $c['class'] }}"></div>
                            <div class="p-2.5 bg-white">
                                <div class="text-xs font-bold text-gray-800">{{ $c['name'] }}</div>
                                <div class="text-[10.5px] text-gray-400 font-mono">{{ $c['token'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 leading-relaxed mt-3 max-w-2xl">
                    <code class="text-[11px]">gold</code> (11 shade, 50–950) adalah satu-satunya warna aksen
                    utama di modul IT — dipakai untuk tombol utama, link, highlight, dan sidebar. Didefinisikan
                    di <code class="text-[11px]">tailwind.config.js</code>, tidak menimpa token
                    <code class="text-[11px]">ink</code>/<code class="text-[11px]">accent</code> milik modul lain.
                </p>
            </section>

            {{-- Status --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">Warna Status</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ([
                        ['label' => 'Success', 'bg' => 'bg-emerald-100', 'fg' => 'text-emerald-700', 'desc' => 'Aksi/perubahan berhasil disimpan.'],
                        ['label' => 'Warning', 'bg' => 'bg-amber-100', 'fg' => 'text-amber-700', 'desc' => 'Perlu perhatian, mis. mode pemeliharaan aktif.'],
                        ['label' => 'Danger', 'bg' => 'bg-red-100', 'fg' => 'text-red-700', 'desc' => 'Aksi merusak/menghapus atau gagal.'],
                        ['label' => 'Info', 'bg' => 'bg-cyan-100', 'fg' => 'text-cyan-700', 'desc' => 'Info netral, bukan aksi.'],
                    ] as $s)
                        <div class="glass-panel p-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-bold {{ $s['bg'] }} {{ $s['fg'] }} mb-2">
                                <span class="w-1.5 h-1.5 rounded-full {{ $s['fg'] }} bg-current"></span>{{ $s['label'] }}
                            </span>
                            <p class="text-[11.5px] text-gray-500 leading-snug">{{ $s['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 leading-relaxed mt-3 max-w-2xl">
                    Pakai token Tailwind bawaan yang sudah pas (<code class="text-[11px]">emerald</code>/<code class="text-[11px]">amber</code>/<code class="text-[11px]">red</code>/<code class="text-[11px]">cyan</code>-500) — tidak perlu token custom baru.
                </p>
            </section>

            {{-- Tipografi --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">Tipografi</h3>
                <div class="glass-panel p-6">
                    <div class="text-3xl font-extrabold text-gray-800">Inter — Display / Heading 800</div>
                    <div class="text-xl font-bold text-gray-800 mt-2.5">Judul Section &amp; Kartu — 600/700</div>
                    <div class="text-sm text-gray-800 mt-2.5">Body teks reguler 400/500 — label form, deskripsi, isi tabel.</div>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed mt-3 max-w-2xl">
                    Font <code class="text-[11px]">Inter</code> (kelas <code class="text-[11px]">font-it</code>)
                    dimuat khusus untuk halaman modul IT lewat <code class="text-[11px]">@@import</code> di
                    <code class="text-[11px]">it/partials/sidebar.blade.php</code> — font global
                    <code class="text-[11px]">font-sans</code> (Plus Jakarta Sans) milik modul lain tidak berubah.
                </p>
            </section>

            {{-- Tombol --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">Tombol</h3>
                <div class="glass-panel p-6 flex flex-wrap gap-3">
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-gradient-to-r from-gold-500 to-gold-600 text-white shadow-[0_4px_12px_-2px_rgba(200,155,44,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(200,155,44,0.5)] transition">Primary</button>
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Secondary</button>
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-[0_4px_12px_-2px_rgba(16,185,129,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(16,185,129,0.5)] transition">Success</button>
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-[0_4px_12px_-2px_rgba(245,158,11,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(245,158,11,0.5)] transition">Warning</button>
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-gradient-to-r from-red-500 to-red-600 text-white shadow-[0_4px_12px_-2px_rgba(239,68,68,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(239,68,68,0.5)] transition">Danger</button>
                    <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg bg-transparent border border-gray-300 text-gray-700 hover:bg-gray-50 transition">Outline</button>
                </div>
            </section>

            {{-- Badge --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">Badge</h3>
                <div class="glass-panel p-6 flex flex-wrap gap-2">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gold-100 text-gold-700">Primary</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Success</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Warning</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Danger</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">Info</span>
                </div>
            </section>

            {{-- Card + Form --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-3">Card &amp; Form</h3>
                <div class="glass-panel p-6 max-w-md">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                        <h4 class="font-semibold text-gray-800">Contoh Card</h4>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gold-100 text-gold-700">Label</span>
                    </div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="text-red-500">*</span></label>
                    <input type="text" placeholder="Contoh input" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:border-gold-500 focus:ring-2 focus:ring-gold-500">
                </div>
                <p class="text-[11.5px] text-gray-500 leading-relaxed mt-3 max-w-md">
                    Card: putih, radius 12px (<code class="text-[11px]">rounded-xl</code>), shadow lembut, padding 1.5rem.
                    Label wajib pakai tanda <span class="text-red-500 font-medium">*</span> merah. Input focus ring gold.
                </p>
            </section>

        </div>
    </div>
</x-app-layout>
