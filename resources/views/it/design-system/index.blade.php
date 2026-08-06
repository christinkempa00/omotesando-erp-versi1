<x-app-layout sidebar="it">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Design System — Allez ERP
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <p class="text-sm text-ink-muted leading-relaxed max-w-2xl">
                Referensi visual &amp; komponen inti. Dipakai konsisten di semua modul
                (GA, SCM, IT, Head) supaya user tidak perlu belajar ulang pola UI
                tiap pindah modul. Sumber: proyek Claude Design "Allez Group ERP
                Redesign" — lihat <code class="text-xs">.design-sync/NOTES.md</code>
                untuk detail token &amp; keputusan scope.
            </p>

            {{-- Warna --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-ink-muted mb-3">Palet Warna</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach ([
                        ['name' => 'Ink', 'class' => 'bg-ink', 'token' => 'text-ink'],
                        ['name' => 'Ink Muted', 'class' => 'bg-ink-muted', 'token' => 'text-ink-muted'],
                        ['name' => 'Accent — Terracotta', 'class' => 'bg-accent', 'token' => 'bg-accent'],
                        ['name' => 'Brass', 'class' => 'bg-brass', 'token' => 'bg-brass'],
                        ['name' => 'Hairline', 'class' => 'bg-hairline', 'token' => 'border-hairline'],
                    ] as $c)
                        <div class="rounded-xl overflow-hidden border border-hairline">
                            <div class="h-16 {{ $c['class'] }}"></div>
                            <div class="p-2.5 bg-white">
                                <div class="text-xs font-bold text-ink">{{ $c['name'] }}</div>
                                <div class="text-[10.5px] text-ink-muted font-mono">{{ $c['token'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-ink-muted leading-relaxed mt-3 max-w-2xl">
                    Dasar netral hangat (bukan abu-abu kebiruan generic) supaya selaras
                    dengan identitas F&amp;B tanpa jadi ramai. Terracotta (<code class="text-[11px]">accent</code>)
                    satu-satunya warna aksen utama — dipakai untuk aksi utama (tombol,
                    link, highlight), bukan dekorasi.
                </p>
            </section>

            {{-- Status --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-ink-muted mb-3">Warna Status (konsisten lintas modul)</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach ([
                        ['label' => 'Pending', 'bg' => 'bg-status-pending-bg', 'fg' => 'text-status-pending-fg', 'desc' => 'Menunggu tindakan approver di step manapun.'],
                        ['label' => 'Approved', 'bg' => 'bg-status-approved-bg', 'fg' => 'text-status-approved-fg', 'desc' => 'Disetujui penuh, seluruh step selesai.'],
                        ['label' => 'Rejected', 'bg' => 'bg-status-rejected-bg', 'fg' => 'text-status-rejected-fg', 'desc' => 'Ditolak di salah satu step approval.'],
                        ['label' => 'Received', 'bg' => 'bg-status-received-bg', 'fg' => 'text-status-received-fg', 'desc' => 'Barang sudah diterima & dikonfirmasi outlet.'],
                        ['label' => 'Discrepancy', 'bg' => 'bg-status-discrepancy-bg', 'fg' => 'text-status-discrepancy-fg', 'desc' => 'Selisih qty terdeteksi otomatis saat terima.'],
                    ] as $s)
                        <div class="rounded-xl border border-hairline p-3.5 bg-white">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11.5px] font-bold {{ $s['bg'] }} {{ $s['fg'] }} mb-2">
                                <span class="w-1.5 h-1.5 rounded-full {{ $s['fg'] }} bg-current"></span>{{ $s['label'] }}
                            </span>
                            <p class="text-[11.5px] text-ink-muted leading-snug">{{ $s['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-ink-muted leading-relaxed mt-3 max-w-2xl">
                    5 status tetap — warna &amp; label ini SAMA di GA, SCM, dan modul lain
                    lewat helper <code class="text-[11px]">*::statusBadgeColor()</code> tiap
                    model. Kontrak visual paling penting: user pindah dari tabel GA ke
                    tabel SCM tanpa belajar ulang arti warna.
                </p>
            </section>

            {{-- Tipografi --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-ink-muted mb-3">Tipografi</h3>
                <div class="bg-white border border-hairline rounded-2xl p-6">
                    <div class="text-3xl font-extrabold text-ink">Plus Jakarta Sans — Display / Heading 800</div>
                    <div class="text-xl font-bold text-ink mt-2.5">Judul Section &amp; Kartu — 600/700</div>
                    <div class="text-sm text-ink mt-2.5">Body teks reguler 400/500 — dipakai untuk label form, deskripsi, isi tabel yang bukan angka.</div>
                    <div class="text-[13px] font-mono text-ink-muted mt-2.5">JetBrains Mono — nomor dokumen, kode QR, kolom angka/nominal tabular (0001/VII/PB/2026, Rp 8.400.000)</div>
                </div>
                <p class="text-xs text-ink-muted leading-relaxed mt-3 max-w-2xl">
                    Plus Jakarta Sans dipilih karena humanist (hangat, bukan generic
                    admin-panel) tapi angkanya tetap jelas di tabel padat. Monospace
                    khusus kode &amp; nominal supaya kolom angka rapi sejajar — penting
                    untuk laporan.
                </p>
            </section>

            {{-- Komponen --}}
            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-ink-muted mb-3">Komponen Inti</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div class="bg-white border border-hairline rounded-2xl p-5">
                        <div class="text-sm font-bold text-ink mb-3">Kartu Ringkasan Angka (KPI)</div>
                        <div class="border border-hairline rounded-xl p-4 w-44">
                            <div class="text-[11.5px] text-ink-muted font-semibold">Pengajuan Pending</div>
                            <div class="text-2xl font-extrabold font-mono text-ink mt-1.5">18</div>
                            <div class="text-[11px] text-status-pending-fg font-semibold mt-1">● Perlu approval Anda</div>
                        </div>
                        <p class="text-[11.5px] text-ink-muted leading-relaxed mt-3">Angka besar + label kecil di atas + micro-status di bawah. Dipakai identik di semua dashboard modul.</p>
                    </div>

                    <div class="bg-white border border-hairline rounded-2xl p-5">
                        <div class="text-sm font-bold text-ink mb-3">Approval Stepper — <code class="text-[11px]">&lt;x-approval-stepper&gt;</code></div>
                        <x-approval-stepper :approvals="$demoSteps" direction="horizontal" />
                        <p class="text-[11.5px] text-ink-muted leading-relaxed mt-3">Titik hijau = selesai, cincin terracotta = sedang di sini, abu-abu = belum. Prop <code class="text-[11px]">direction</code>: <code class="text-[11px]">horizontal</code> (ringkas) atau <code class="text-[11px]">vertical</code> (detail page, dgn meta approver &amp; tanggal).</p>
                    </div>

                    <div class="bg-white border border-hairline rounded-2xl p-5">
                        <div class="text-sm font-bold text-ink mb-3">Stepper — contoh ditolak</div>
                        <x-approval-stepper :approvals="$rejectedSteps" direction="vertical" />
                    </div>

                    <div class="bg-white border border-hairline rounded-2xl p-5">
                        <div class="text-sm font-bold text-ink mb-3">Upload Foto Wajib</div>
                        <div class="w-40 h-28 rounded-xl border-2 border-dashed border-accent/40 bg-accent-tint flex flex-col items-center justify-center gap-1.5">
                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-sm">📷</div>
                            <div class="text-[10.5px] text-accent font-bold">Ambil / unggah foto</div>
                        </div>
                        <p class="text-[11.5px] text-ink-muted leading-relaxed mt-3">State kosong (dashed) → preview thumbnail setelah diambil → badge "WAJIB" merah kalau submit dicoba tanpa foto. Dipakai di Surat Jalan &amp; Konfirmasi Terima (lihat <code class="text-[11px]">scm/deliveries</code>).</p>
                    </div>

                    <div class="bg-white border border-hairline rounded-2xl p-5">
                        <div class="text-sm font-bold text-ink mb-3">Kode QR (label &amp; dokumen)</div>
                        <div class="w-28 h-28 rounded-lg overflow-hidden [&>svg]:w-full [&>svg]:h-full">{!! $demoQr !!}</div>
                        <p class="text-[11.5px] text-ink-muted leading-relaxed mt-3">QR asli (Endroid), bukan placeholder — isi: kode label/dokumen. Selalu sertakan kode teks di bawahnya untuk fallback manual scan gagal.</p>
                    </div>

                </div>
            </section>

            <section>
                <h3 class="text-xs font-bold uppercase tracking-wide text-ink-muted mb-3">Navigasi &amp; Layout</h3>
                <div class="bg-white border border-hairline rounded-2xl p-5 text-[12.5px] text-ink-muted leading-relaxed max-w-3xl">
                    Desktop (Head Office/Finance/HR/GA/IT): sidebar kiri persisten,
                    konten data-dense di kanan — modul yang tidak diaktifkan Head
                    untuk role tsb otomatis hilang dari sidebar (bukan disabled/abu-abu,
                    supaya menu tidak berantakan). Gudang &amp; Outlet: alur mobile-first,
                    satu layar = satu keputusan, tombol besar (min. 48px), minim ketik —
                    lihat wizard di <code class="text-[11px]">scm/deliveries/create</code>
                    (Buat Surat Jalan) dan bagian Konfirmasi Terima di
                    <code class="text-[11px]">scm/deliveries/show</code>.
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
