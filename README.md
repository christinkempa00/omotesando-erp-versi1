# PT Omotesando ERP

ERP internal untuk grup restoran Omotesando (Ironiku, The Cutler, Ask for
Patty, Zodiac, Patty and Sons, Central Kitchen, Central Storage). Dibangun
di atas Laravel 12 + Blade + Tailwind + Alpine.js.

## Riwayat Perubahan

Catatan perubahan per tanggal — detail lengkap tiap fitur tetap ada di
"Daftar Modul" & "Alur Kerja per Modul" di bawah, bagian ini cuma ringkasan
kapan sesuatu berubah.

- **21/08/2026** — **Asset Request: kategori baru, Sub Total/PPH/Diskon/
  Total, field Total per item** — (1) 5 pilihan Kategori/Tujuan direvisi
  jadi Perbaikan & Pemeliharaan Fasilitas / Infrastruktur & Sistem
  Pendukung / Keselamatan, Kebersihan & Higienitas / Kebutuhan Operasional
  & Pengembangan / Maintenance Stock (data lama tetap valid — cuma label
  teks yg berubah, bukan struktur data); (2) catatan "wajib diisi" yg
  sebelumnya berulang di tiap baris item sekarang jadi 1 catatan saja di
  bawah daftar item; (3) tiap baris item sekarang punya field "Total"
  sendiri (qty × Harga Satuan, otomatis, read-only) selain subtotal yg
  sudah ada sebelumnya; (4) form & dokumen PDF sekarang mendukung PPH &
  Diskon opsional (persentase dari Sub Total, bisa dipakai keduanya
  sekaligus) — urutan tampilan konsisten di form/detail/PDF: Sub Total →
  Diskon (kalau diisi) → PPH (kalau diisi) → Total.
- **20/08/2026** — **Work Log: waktu mulai/berakhir + durasi otomatis,
  teknisi dropdown, diagram distribusi kerja** — (1) field "Waktu
  Pengerjaan" tunggal dipecah jadi Waktu Mulai & Waktu Berakhir (wajib
  diisi keduanya), durasi dihitung otomatis (tidak disimpan sbg kolom,
  selalu dikomputasi ulang dari kedua waktu tsb) dan ditampilkan di
  daftar & detail; (2) Teknisi in Charge diubah dari teks bebas jadi
  dropdown pilihan tetap (Bangkat/Toni/Widi) — data lama di luar daftar
  ini (kalau ada) tetap tampil & bisa dilihat saat diedit, tapi harus
  diganti ke salah satu dari 3 nama itu utk bisa disimpan ulang; (3)
  ditambahkan diagram bulat "Distribusi Pekerjaan per Teknisi" di
  halaman daftar Work Log (mengikuti filter outlet/kategori/tanggal yg
  aktif) utk melihat teknisi mana yg paling banyak menangani pekerjaan;
  (4) kategori pengerjaan bertambah 1 pilihan baru: "Pendampingan
  Vendor".
- **18/08/2026** — **[SUSULAN 2] Fix form Stok Seragam** — (1) field
  Ambang Low Stock diperbaiki jadi benar-benar opsional (sebelumnya
  validasi server keliru menolak form yang mengosongkan field ini,
  padahal dokumentasi sudah lama menyatakan field ini opsional — sekarang
  default 0 kalau dikosongkan, konsisten dengan kolom DB yang memang
  `default(0)`); (2) form "+ Varian Baru" sekarang otomatis memperingatkan
  (live, tanpa submit) kalau nama Tipe Seragam yang diketik sudah dipakai
  di outlet yang sama — nama sama di outlet berbeda tidak memicu
  peringatan, dan peringatan ini tidak memblokir penyimpanan. Manual Book
  dibump ke v1.9.
- **18/08/2026** — **[SUSULAN] Konsistensi klik-baris untuk buka detail** —
  daftar Stok Seragam, Jadwal Pemeliharaan, dan Kartu Seragam Karyawan
  sekarang mengikuti pola yang sudah dipakai di Inventaris Aset/Work
  Log/GA Request: klik baris mana pun langsung membuka halaman detail,
  tombol/tautan "Detail" terpisah dihapus. Khusus daftar Stok Seragam:
  ikon hapus per baris ukuran juga dihapus dari daftar (hapus varian
  ukuran sekarang lewat halaman detail varian, klik barisnya dulu) —
  hapus satu grup varian sekaligus tetap di halaman daftar. Manual Book
  dibump ke v1.8; Dokumentasi Teknis tidak berubah (murni navigasi UI,
  tidak menyentuh skema/logika bisnis).
- **18/08/2026** — **3 perubahan modul GA (GA Request, Uniform Stock, Uniform
  Record)** — (1) Label field "Diajukan oleh (GA)" pada formulir GA Request
  diganti jadi **"Pemohon"** (placeholder: "Nama Pemohon"), termasuk di
  dokumen PDF (Berita Acara) dan pesan validasi — nama kolom DB
  (`requester_name`) & aturan validasi tidak berubah. (2) Aksi manual
  **Issue, Adjustment, dan Disposal** pada halaman detail Stock Management
  dihapus — tersisa hanya **Restock**, karena Issue tumpang tindih dengan
  alur "Serah Terima Baru" (sudah otomatis mengurangi stok), dan pencatatan
  stok rusak sekarang cukup lewat aksi "Tandai Dikembalikan" (kondisi
  Rusak) di Kartu Seragam Karyawan — tidak ada lagi cara menulis-off stok
  rusak lewat aplikasi. (3) Daftar Kartu Seragam Karyawan (Uniform
  Inventory) sekarang punya tombol **Export Excel/PDF** sendiri, pola sama
  dengan Stock Management. Manual Book dibump ke v1.7, Dokumentasi Teknis
  ke v1.4.
- **14/08/2026** — **[SUSULAN 2] Seragamkan label tombol/aksi modul GA ke
  Bahasa Indonesia** — setelah revert penuh ke Bahasa Indonesia (entri di
  bawah), sejumlah label tombol masih berbeda-beda penyebutan meski sudah
  sama-sama Bahasa Indonesia (mis. "+ Jadwalkan" vs "+ Pemeliharaan",
  "Tandai Pekerjaan Selesai" vs "Selesai (Complete)"). Diseragamkan satu
  fungsi satu nama sesuai tabel acuan eksplisit: "+ Pemeliharaan" (Jadwal
  Pemeliharaan, termasuk link cepat di Dashboard), "Selesai (Complete)"
  (tombol tandai pekerjaan pemeliharaan selesai), "+ Serah Terima Baru" &
  "Dokumen (PDF)" (Kartu Seragam Karyawan), "Riwayat" & "Lihat Semua"
  (bagian riwayat pergerakan di halaman Stock Management — sebelumnya
  section ini kelewatan masih berjudul "History"), "+ Buat Permintaan",
  "Simpan Draft" (sebelumnya "Simpan sebagai Draft"), dan "Dokumen (PDF)"
  (GA Request), "+ Tambah Work Log" (Work Log). Tidak ada perubahan logika
  bisnis maupun nama route/controller/kolom DB — hanya teks yang tampil ke
  pengguna. Manual Book dibump ke v1.6 mengikuti wording baru ini;
  Dokumentasi Teknis (v1.3) tidak berubah karena tidak menyebut label
  tombol spesifik ini.
- **14/08/2026** — **[SUSULAN] Seluruh UI modul GA dikembalikan ke Bahasa
  Indonesia sepenuhnya** — percobaan menerjemahkan ke Bahasa Inggris
  (13/08, lalu dipersempit jadi dwibahasa di entri sebelumnya hari ini)
  dibatalkan total per instruksi eksplisit. Semua halaman daftar/index,
  detail/show, sidebar, dan tombol navigasi ("+ Aset Baru", "Hapus",
  "Detail", dst.) di seluruh sub-modul (Dashboard, Asset Inventory, Asset
  Maintenance Schedule, Uniform Inventory, GA Request, Work Log) kini
  Bahasa Indonesia — bukan cuma 6 formulir "tambah data" dari entri
  sebelumnya. Pengecualian yang sengaja tetap Bahasa Inggris (konvensi
  lama, sudah begitu sebelum 13/08): istilah Jenis Pekerjaan & Prioritas
  pada Jadwal Pemeliharaan, aksi stok Restock/Issue/Adjustment/Disposal,
  dan nama modul "Work Log"/"GA Request" itu sendiri. Manual Book (v1.5)
  & Dokumentasi Teknis (v1.3) modul GA ikut diperbarui menyesuaikan.
- **14/08/2026** — **6 formulir "tambah data" modul GA dikembalikan ke
  Bahasa Indonesia sepenuhnya** (label field, judul formulir, tombol
  Simpan/Batal) — Tambah Aset, Jadwalkan Pemeliharaan, Serah-terima Seragam,
  Tambah Stok Seragam, Pengajuan GA, dan Work Log, termasuk halaman
  Ubah/Edit masing-masing (satu partial `_form.blade.php`/`_edit-form.blade.php`
  dipakai bersama utk Tambah & Ubah). Bagian lain modul GA (halaman
  daftar/index, detail/show, menu sidebar, tombol navigasi) sempat tetap Bahasa
  Inggris sesuai perubahan sebelumnya (13/08) — jadi modul GA sekarang
  dwibahasa dengan sengaja: isian data pakai Indonesia, chrome halaman
  pakai Inggris. Manual Book (v1.4) & Dokumentasi Teknis (v1.2) modul GA
  ikut diperbarui menyesuaikan.
- **13/08/2026** — **[BUG SERIUS] Semua export PDF di seluruh aplikasi
  sempat rusak (500 "Cannot resolve public path")** di production Hostinger
  — bug lama dari setup split-hosting `public_html`, TERNYATA belum
  sepenuhnya kebeneran oleh fix `usePublicPath()` sebelumnya (06/08) krn
  package `barryvdh/laravel-dompdf` tidak memakai helper `public_path()`
  Laravel, dia baca `base_path('public')` langsung lewat config
  `dompdf.public_path`. Sekarang di-set otomatis di `AppServiceProvider`
  pakai deteksi folder sibling yang sama seperti fix sebelumnya. Sudah
  dites: export PDF Aset & dokumen Serah Terima Seragam kembali normal.
  Sekalian dibenahi: gambar tanda tangan di dokumen Serah Terima Seragam
  diganti ke base64-embed (pola sama seperti dokumen GA Request) supaya
  tidak bergantung ke path lokal yang rawan gagal di hosting split spt ini.
- **13/08/2026** — **"Revisi Versi 1"**, 4 perubahan modul GA: (1) **Semua
  halaman modul GA (kecuali isian/list data) diterjemahkan ke Bahasa
  Inggris** — label field, judul halaman, tombol, pesan flash, pesan error
  validasi, di seluruh Dashboard, Asset Inventory, Asset Maintenance
  Schedule, Uniform Inventory (+ Stock Management & movement history),
  Asset Request, dan Work Log; modul lain TIDAK disentuh (`APP_LOCALE`
  tetap `id` global, format tanggal Indonesia di halaman GA dipertahankan
  lewat `->locale('en')` eksplisit per pemanggilan `translatedFormat()`,
  bukan ubah setting global). Dokumen PDF formal (Berita Acara Serah
  Terima, dokumen GAR) sengaja tidak diterjemahkan. (2) **Uniform Stock
  Management**: tombol "Serah-terima" dihapus dari halaman ini (sudah ada
  halaman Uniform Inventory sendiri); field Kondisi dihapus dari form
  Tambah Stok — seragam baru otomatis berstatus "Bagus", field Kondisi
  cuma muncul lagi saat Pengembalian. (3) **Asset Inventory**: tombol
  "Pilih Aset" dipindah ke sebelah kanan filter bar (dulu di header
  halaman). (4) **Asset Maintenance Schedule**: kalender mingguan
  per-jam dihapus (dianggap over-design), diganti kalender kecil bulanan
  + list "Jadwal Mendatang" saja — tanggal yang ada jadwalnya ditandai
  titik oranye, klik tanggal itu buka tiket detail jadwal (kalau lebih
  dari satu jadwal di tanggal yang sama, tampil list dulu sebelum pilih).
- **12/08/2026** — **Central Storage dihapus dari semua dropdown outlet
  operasional modul GA** (Asset Inventory, Asset Maintenance Schedule, GA
  Dashboard breakdown per-outlet — Asset Request sudah lebih dulu) — dibuat
  konstanta bersama `Branch::GA_OUTLETS`. Central Storage TETAP outlet aktif
  penuh & tidak disentuh di SCM/Purchasing/IT (gudang sungguhan yang mereka
  kelola). Untuk 22 aset yang sudah terlanjur berlokasi di Central Storage,
  dropdown Edit Aset & Edit Jadwal tetap menampilkan Central Storage KHUSUS
  utk aset/jadwal itu (via param `$alwaysInclude` baru di
  `Branch::orderedOutlets()`) — supaya data lama tidak hilang/berubah keliru
  saat disimpan ulang.
- **12/08/2026** — 4 perubahan: (1) **"Minta Aset"** (Quick Request GA)
  disambungkan lagi ke Telegram (grup yang sama dgn notifikasi Jadwal
  Pemeliharaan) — modal kembali jadi "📢 PEMBERITAHUAN PERMINTAAN" dgn tombol
  "Kirim ke Telegram", pesan berhasil dites terkirim ke grup. (2) **Jadwal
  Pemeliharaan**: field "Jam" diganti "Jam Mulai" + field baru **"Waktu
  Selesai"** (opsional, harus setelah jam mulai kalau keduanya diisi) —
  tersimpan sbg `scheduled_end_time`, tampil sbg rentang jam (mis. "09:00 –
  11:00") di halaman detail. (3) **Asset Request**: label "Nama Pemohon"
  diganti **"Diajukan oleh"**; outlet **Central Storage dihapus** dari
  dropdown outlet-nya (gudang, bukan lokasi yg mengajukan permintaan aset
  operasional) — urutan outlet yg tersisa tetap ikut urutan baku yg sama
  dipakai di seluruh modul GA (`Branch::ASSET_REQUEST_OUTLETS`, subset baru
  dari `Branch::OUTLET_ORDER`, pola sama seperti subset Work Log/Seragam).
- **12/08/2026** — **Uniform Stock Management**: foto lampiran varian sekarang
  ditampilkan langsung di kartu grup halaman Stock Management (bersebelahan
  dgn jumlah stok), supaya varian yang mirip bisa dibedakan tanpa buka detail.
  Form "Tambah Varian"/"+ Tambah Ukuran Baru" sekarang menampilkan kotak
  **"Sisa Stok Saat Ini"** (read-only, per ukuran) di atas kolom input jumlah
  saat dibuka utk grup yang sudah ada — supaya user tahu stok existing
  sebelum menambah jumlah baru (yg memang bersifat menambah, bukan menimpa).
  Konfirmasi hapus ("Hapus... ini?") sudah ada di semua tombol hapus modul
  ini sejak awal — dicek ulang, tidak ada yg terlewat. Status **"Dalam
  Pemeliharaan"** dihapus khusus dari Uniform Stock (Asset Inventory tetap
  punya status ini) — seragam rusak langsung ditandai "Rusak", tidak ada
  konsep "sedang diperbaiki vendor" seperti aset fisik.
- **12/08/2026** — Pembersihan kode: 5 file Blade component sisa scaffolding
  default Laravel Breeze yang tidak pernah dipakai (application-logo,
  dropdown, dropdown-link, nav-link, responsive-nav-link) dihapus dari lokal
  & production. Semua kolom/nilai harga di seluruh modul diseragamkan format
  **"Rp"** — Buku Besar (Finance) sebelumnya tampil angka polos tanpa "Rp",
  sekarang konsisten dgn laporan keuangan lain. Export Excel **Nilai
  Persediaan** (SCM) sekarang pakai format angka Excel asli (`"Rp" #,##0`)
  utk kolom Biaya/Unit & Nilai — tetap angka murni (bisa dijumlah/sort di
  Excel), cuma tampilannya pakai prefix Rp. Field input harga (**Harga
  Beli** & **Nilai Depresiasi** di Tambah/Edit Aset, **Estimasi Biaya** di
  Tambah/Edit Jadwal Pemeliharaan) sekarang juga tampil format Rp langsung
  saat diketik (mis. "Rp 34.344") — sebelumnya cuma raw `<input type=number>`
  polos tanpa format, sekarang pakai pola input rupiah bertitik-ribuan yang
  sama seperti di form GA Request.
- **12/08/2026** — Bot Telegram notifikasi Jadwal Pemeliharaan dipindah dari
  chat pribadi ke **grup Telegram** (chat id `-5461390868`) — `.env` diupdate
  di lokal & production, sudah dites kirim pesan berhasil. Label field
  "Kondisi" di **Asset Inventory** (form tambah/edit, filter, tabel list,
  export Excel/PDF) diganti jadi **"Status"** supaya konsisten dgn istilah
  yang sudah dipakai di Asset Maintenance Schedule — nilainya tetap sama
  (Bagus/Dalam Pemeliharaan/Rusak), cuma penamaan label yg diseragamkan biar
  tidak membingungkan antar 2 modul yang saling terkait.
- **11/08/2026** — Bot Telegram sekarang eksklusif utk **Asset Maintenance
  Schedule** — notifikasi tambah/ubah/hapus jadwal TETAP jalan, tapi
  notifikasi Asset Inventory CRUD, Uniform Stock Management CRUD, approval
  Asset Request (+ kirim dokumen PDF final ke Telegram pribadi pemohon),
  Quick Request, dan approval Material Request/Production Batch/Purchase
  Order/Purchase Requisition SENGAJA dihapus (approval masih tahap
  pengembangan, belum diperlukan). Modal "Minta Aset" (Quick Request)
  disesuaikan teksnya krn tidak lagi terhubung ke Telegram. **Fitur baru**:
  pengingat Telegram otomatis H-1 hari, H-1 jam, & **keterlambatan** (jadwal
  yang lewat tanggal & belum selesai — dikirim ulang tiap 15 menit selama
  masih terlambat, pakai definisi "Terlambat" yang sama dgn badge di
  halaman lain) sebelum/soal jadwal pemeliharaan (command
  `maintenance:send-reminders`, jalan tiap 15 menit lewat scheduler —
  **butuh cron `php artisan schedule:run` didaftarkan tiap menit di
  hPanel Hostinger**, lihat DEPLOY.md). Form tambah/edit Jadwal Pemeliharaan
  dirombak: Outlet & Lokasi sekarang di atas field Aset (urutan dropdown
  outlet sama persis dgn form Tambah Aset), Lokasi jadi dropdown tetap
  (Kitchen/Floor/Bar/Lainnya, sama seperti Aset) bukan teks bebas lagi, dan
  pilihan Aset otomatis tersaring mengikuti Outlet+Lokasi yang dipilih.
  Dropdown Lokasi di Asset Inventory juga ditambah pilihan **"Lainnya"**
  (sebelumnya cuma Kitchen/Floor/Bar, padahal data lama sudah ada yang
  bernilai "Lainnya" — jadi sebelumnya tidak bisa dipilih ulang lewat form).
- **10/08/2026** — Revisi V1: (1) Login pakai label "Username" (kolom di
  baliknya tetap email), fitur lupa password self-service dihapus total
  (reset password sekarang cuma lewat IT); akun **GA** dibuat
  (`ga@allez-group.com`). (2) Staf Penanggung Jawab, Foto Aset, & Foto SN di
  **Asset Inventory** sekarang wajib diisi saat tambah aset — utk edit aset
  lama, foto cuma wajib diupload ulang kalau asetnya belum punya foto sama
  sekali. (3) Fitur Export .xlsx/.pdf ditambahkan ke **Batch Produksi**,
  **Stok Mendekati ED**, & **Nilai Persediaan** (SCM). (4) Semua dokumen PDF
  di seluruh modul (sebelumnya cuma Asset Request) sekarang tampil preview
  inline di tab browser dulu, bukan langsung terunduh. (5) Popup "Modul
  Sedang Dalam Pemeliharaan" — modul yang ditandai IT lewat Kontrol Modul
  sekarang muncul sebagai popup di halaman tujuan redirect, bukan halaman
  penuh terpisah lagi.
- **10/08/2026** — Data dummy dihapus dari **Asset Maintenance Schedule**,
  **Asset Request** (+ Quick Request), dan **Uniform Stock Management**
  (stok, movement, record) — baik di local maupun production — karena
  ketiganya belum dipakai beneran. **Asset Inventory** sengaja tidak
  disentuh (128 aset itu data real). Baris `approvals` milik modul lain
  (mis. Material Request SCM) juga sengaja tidak ikut terhapus krn tabel
  itu dipakai bareng lewat trait `Approvable`. Fitur Export .xlsx/.pdf
  ditambahkan ke **Uniform Stock Management** (sebelumnya cuma ada
  endpoint xlsx tanpa tombol di halaman; sekarang keduanya tersedia,
  konsisten dgn pola di Asset Inventory).
- **08/08/2026** — Notifikasi Telegram ditambah ke 3 area GA (pakai bot
  Telegram khusus modul GA — `TELEGRAM_BOT_TOKEN`/`TELEGRAM_CHAT_ID` di
  `.env`): **Asset Inventory** (tambah/ubah/hapus aset), **Asset
  Maintenance Schedule** (tambah/ubah/hapus jadwal pemeliharaan), dan
  **Uniform Stock Management** (tambah/ubah/hapus varian, termasuk hapus
  1 grup ukuran sekaligus). Asset Request (persetujuan) & Work Log
  sengaja tidak disentuh — Asset Request sudah punya notifikasi sendiri
  sejak fitur tanda tangan digital, Work Log belum perlu.
- **08/08/2026** — Asset Request (GaRequest): tombol submit dipecah jadi 2
  — "Simpan sebagai Draft" (validasi longgar, item boleh belum lengkap,
  tanda tangan belum wajib) dan "Kirim Pengajuan" (dulu "Simpan
  Pengajuan" — validasi penuh + alur approval mulai jalan). Draft bisa
  dibuka lagi lewat tombol "Lanjutkan Draft" di halaman detail utk
  dilengkapi & dikirim kapan saja (halaman Edit baru). Harga Satuan item
  sekarang wajib diisi > 0 saat "Kirim Pengajuan" (sebelumnya 0 lolos
  validasi). Tampilan daftar item (detail pengajuan) digabung: kolom Tipe
  ditempel ke kolom Item dengan pemisah "-", kolom lain (Qty/Satuan/Harga
  Satuan/Vendor/Subtotal) disesuaikan lebar-nya. Dokumen PDF sekarang
  tampil preview inline di tab browser dulu (klik "Dokumen"), bukan
  langsung terunduh; area tanda tangan di PDF juga diperbesar. Tanda
  tangan pemohon yang sebelumnya cuma tersimpan di database sekarang ikut
  tertempel sebagai gambar di kolom "Diajukan oleh" pada dokumen PDF
  (sempat kelewat saat fitur tanda tangan pertama kali dibuat).
- **08/08/2026** — Pilot tanda tangan GaRequest: PDF final yang otomatis
  terkirim ke Telegram (lihat entri 07/08/2026) sekarang JUGA dikirim
  langsung ke chat pribadi pemohon lewat Telegram, kalau pemohon sudah
  mengisi `telegram_chat_id` di halaman Profile-nya sendiri (field baru,
  opsional — kalau kosong, dokumen tetap terkirim ke grup seperti biasa,
  tidak error). Ditambah Feature Test (`tests/Feature/GA/GaRequestSignatureTest.php`)
  yang mengunci 4 perilaku inti pilot: submit tanpa tanda tangan pemohon
  ditolak, approve tanpa tanda tangan approver ditolak, dokumen final cuma
  terkirim sekali status benar-benar selesai (bukan di tengah proses), &
  tanda tangan tersimpan dipakai ulang otomatis di approve berikutnya.
- **07/08/2026** — Pilot tanda tangan digital berbasis gambar (bukan
  e-signature tersertifikasi, sekadar jejak audit internal) di alur
  Asset Request (GaRequest): pemohon wajib tanda tangan saat submit
  pengajuan (`ga_requests.requester_signature_path`), approver Head wajib
  tanda tangan saat **approve** step-nya (`approvals.signature_path`,
  ditegakkan di kedua tempat aksi approve bisa dilakukan — halaman
  pengajuan & Approval Inbox) — **reject sengaja tidak butuh tanda
  tangan**. Semua pakai komponen `<x-signature-pad>` yang sama (kanvas
  gambar, validasi wajib di client & server, tidak bisa di-bypass lewat
  request manual). Tanda tangan bisa disimpan sebagai default akun
  (`users.signature_path`) supaya dipakai ulang tanpa gambar ulang tiap
  kali (opsional lewat centang "simpan sebagai default" saat submit;
  otomatis ter-update tiap approve pakai gambar baru). Tanda tangan ikut
  tertempel di PDF GAR; PDF final otomatis dikirim ke Telegram sekali alur
  approval selesai penuh (kalau bot Telegram sudah dikonfigurasi).
- **05/08/2026** — Uniform Inventory: foto serah terima (bukti pengambilan
  barang/seragam oleh karyawan) sekarang **wajib** diunggah saat mencatat
  serah-terima baru (sebelumnya opsional).
- **04/08/2026** — Modul GA: field `Tipe` ditambah di item Asset Request
  (urutan field: Nama Item → Tipe → Qty → Satuan → Harga Satuan → Vendor),
  Harga Satuan diformat Rupiah otomatis saat mengetik. Uniform Inventory
  dipisah jadi 2 halaman: daftar pemakaian (halaman utama, sidebar) &
  Stock Management (sub-halaman, khusus kelola stok/mutasi).
- **03/08/2026** — Sidebar GA disusun berjenjang dengan label Inggris
  (`Asset Inventory`, `Uniform Inventory`, dst) + seksi `Operational
  Support`. Modul baru **Work Log** (catatan aktivitas teknisi per outlet,
  utk kontrol kinerja).

## Akun Login (Testing)

Semua akun di bawah dibuat oleh `database/seeders/UserSeeder.php` (jalankan
`php artisan db:seed` untuk membuatnya di database lokal). Password semua
akun: **`password`**.

| Role       | Email                          | Catatan |
|------------|---------------------------------|---------|
| Admin + GA | admin@omotesando.test           | Akses penuh ke modul GA & SCM, plus approval Admin di semua alur |
| Head       | head@omotesando.test            | Approval lintas modul + monitoring read-only (tidak bisa input data) |
| IT         | it@omotesando.test              | Kontrol Akses & Mode Pemeliharaan + Papan Kerja Kanban |
| Produksi   | produksi@omotesando.test        | Modul SCM: buat Pengajuan Bahan & Batch Produksi |
| Gudang     | gudang@omotesando.test          | Modul SCM: cetak label, buat Surat Jalan — terikat branch **Central Kitchen** |
| Outlet     | outlet@omotesando.test          | Modul SCM: terima kiriman — terikat branch **Ironiku** |
| Purchasing | purchasing@omotesando.test      | Modul Purchasing: approve Purchase Requisition dari Outlet, lalu buat PO kategori bahan makanan |
| Finance    | finance@omotesando.test         | Modul Purchasing: approval step terakhir (pencairan dana) + catat invoice/pembayaran supplier |

> Role `HR` dan `Cost Control` (lihat `app/Models/Role.php`) sudah
> didaftarkan di sistem tapi belum punya akun seeded maupun modul yang
> memakainya — buat manual lewat `php artisan tinker` atau tambahkan ke
> `UserSeeder` kalau dibutuhkan.

## Daftar Modul

### GA (General Affairs) — `/ga` — role GA, Admin, Finance
Sidebar disusun berjenjang (label Inggris — lihat Riwayat Perubahan utk kapan berubahnya):
`Dashboard` · `Uniform Inventory` (+ sub-item `Stock Management`) · `Asset Inventory` (+ sub-item `Asset Maintenance Schedule`) ·
seksi `Operational Support` (`Asset Request`, `Work Log`).
- **Dashboard** (`/dashboard`) — ringkasan KPI: total aset, total pengajuan, total biaya pemeliharaan, total nilai pengiriman SCM.
- **Asset Request** (`ga.requests.*`, dulu "Pengajuan GA") — pengajuan barang/jasa dengan approval bertingkat, cetak dokumen PDF, pengajuan cepat (quick request).
- **Asset Inventory** (`ga.assets.*`, dulu "Inventaris Aset") — CRUD aset (kode otomatis `AST-0001`, dst.), foto aset & foto serial number, QR code per aset (+ halaman publik scan tanpa login di `/a/{kode_aset}`), cetak label massal, export Excel/PDF.
- **Uniform Inventory** (`ga.uniforms.records.*`) — daftar & CRUD serah-terima/pemakaian seragam per karyawan (issue/return), foto serah terima **wajib** diunggah sbg bukti pengambilan barang, dokumen Berita Acara Serah Terima (PDF). Sub-halaman **Stock Management** (`ga.uniforms.stocks.*`, dipisah dari halaman ini) — stok per varian (outlet/tipe/warna/ukuran), mutasi (restock/adjustment/disposal), riwayat movement lengkap (`ga.uniforms.movements.index`, link "Lihat semua" dari situ). Keduanya satu modul & saling terhubung (issue/return otomatis memutasi stok yang sama).
- **Asset Maintenance Schedule** (`ga.maintenance.*`, dulu "Jadwal Pemeliharaan", sub-item dari Asset Inventory) — CRUD pekerjaan pemeliharaan per aset, nomor otomatis `MNT-2026-0001` (reset per tahun), status (Terjadwal/Sedang Berjalan/Selesai/Dibatalkan), tipe (Preventive/Corrective/Cleaning Inspection/Calibration/Service Vendor/Predictive), prioritas (Emergency/High/Normal), checklist dinamis + progress bar. Tanpa langkah approval — langsung dijadwalkan & diselesaikan oleh tim GA.
- **Work Log** (`ga.worklogs.*`) — catatan aktivitas teknisi per outlet utk kontrol kinerja: tanggal + waktu mulai/berakhir (durasi dihitung otomatis), kategori (Pemeriksaan/Perbaikan/Instalasi/Maintenance/Pendampingan Vendor), outlet (6 outlet berstaf, termasuk Central Kitchen), teknisi in charge (dropdown tetap: Bangkat/Toni/Widi) & teknisi assist (teks bebas), detail pengerjaan (wajib) & hasil pengerjaan (opsional saat dibuat, diisi begitu selesai — status "Selesai"/"Belum Ada Hasil" otomatis), keterangan, lampiran foto (boleh lebih dari satu). Diagram bulat "Distribusi Pekerjaan per Teknisi" di halaman daftar (ikut filter aktif) utk memantau beban kerja tiap teknisi. Tanpa approval, tidak terikat ke Asset/Jadwal Pemeliharaan tertentu — murni log independen.

### Head — `/head` — role Head
Halaman monitoring read-only + approval, terpisah dari halaman operasional GA/SCM:
- Dashboard, Semua Pengajuan (approve/reject), Approval Inbox (generik lintas modul).
- Kontrol Modul — aktif/nonaktifkan modul GA/SCM & atur role yang boleh akses.
- Monitoring Aset, Seragam, Pemeliharaan, dan SCM (rekap + laporan selisih) — semua read-only.

### SCM (Supply Chain / Warehouse) — `/scm` — role Produksi, Gudang, Outlet, Admin
Alur: Pengajuan Bahan → Batch Produksi → Cetak Label → Surat Jalan → Terima Outlet → Laporan Selisih (otomatis).
- **Pengajuan Bahan** (`scm.materials.*`) — diajukan Produksi, disetujui Admin.
- **Batch Produksi** (`scm.batches.*`) — satu batch bisa menghasilkan beberapa produk berbeda; disetujui Admin.
- **Cetak Label** — QR + tanggal kedaluwarsa per produk hasil batch; mencatat stok masuk otomatis di gudang.
- **Surat Jalan** (`scm.deliveries.*`) — dibuat & dikirim Gudang, wajib foto bukti muat, item dipilih otomatis berdasar **FEFO** (kedaluwarsa terdekat) via endpoint `deliveries/available-labels`.
- **Terima Outlet** — konfirmasi qty diterima + wajib foto; selisih qty terdeteksi & tercatat otomatis (Laporan Selisih).
- **Laporan Selisih** (`scm.discrepancies.*`) — dibuat otomatis saat qty diterima ≠ qty dikirim.
- **Rekap Periodik** (`scm.reports.index`) — rekap pengiriman per periode/outlet + export PDF.
- **Stok Mendekati Kedaluwarsa** (`scm.reports.near-expiry`) — daftar batch dengan ED mendekati (ambang hari bisa diatur), dikelompokkan per lokasi.
- **Laporan Nilai Persediaan** (`scm.reports.stock-value`) — nilai stok saat ini (qty × biaya/unit) per outlet & konsolidasi.
- Stok real-time per lokasi & histori pergerakannya tercatat otomatis di balik layar setiap ada cetak label/kirim/terima (tidak ada halaman terpisah, dipakai oleh fitur-fitur di atas).

### Purchasing — `/purchasing` — role Purchasing, Outlet, GA, Finance, Gudang, Admin
Pembelian bahan baku/barang ke supplier, dua alur berbeda tergantung kategori. Head **sengaja tidak** punya akses langsung ke halaman modul ini — approval Head selalu lewat Approval Inbox generik yang sudah ada.
- **Purchase Requisition** (`purchasing.purchase-requisitions.*`) — khusus kategori **food** (bahan makanan): diajukan **Outlet** (nama & qty saja, tanpa harga) → disetujui/ditolak **Purchasing** (1 langkah).
- **Purchase Order** (`purchasing.purchase-orders.*`) — 2 jalur:
  - **food**: dibuat **Purchasing** dari Purchase Requisition yang sudah disetujui (pilih supplier, tujuan pengiriman — biasanya Central Kitchen/Storage, bukan langsung ke outlet pemohon — & isi harga per item); approval PO-nya sendiri cuma 2 langkah (Head → Finance) karena Purchasing sudah menyetujui lewat requisisi.
  - **general** (barang umum): dibuat langsung oleh **GA** tanpa requisisi; approval PO-nya 3 langkah (Purchasing → Head → Finance) karena belum pernah direview Purchasing sebelumnya.
  - Nomor otomatis format `000X/[bulan romawi]/PO/[tahun]` (requisisi pakai kode `PR`, PO pakai kode `PO`).
- **Penerimaan Barang** — konfirmasi barang sampai dari supplier, wajib foto. Gudang menerima kalau PO tujuannya branch miliknya (kategori apa saja); GA menerima kalau kategori 'general' (tidak terikat branch tertentu).
- **Stok real-time** — HANYA PO kategori **food** yang ikut memperbarui stok (`stock_balances`/`stock_movements`, tabel yang sama dipakai modul SCM produksi) — barang kategori general tidak dilacak sebagai stok "batch" sama sekali, cuma tercatat sebagai bukti penerimaan. Laporan **Stok Mendekati Kedaluwarsa** & **Nilai Persediaan** milik SCM otomatis mencakup stok dari sini juga.
- **Invoice Supplier** (`purchasing.supplier-invoices.*`) — domain Finance: catat invoice per penerimaan barang, catat pembayaran (mendukung cicilan/partial), status otomatis unpaid → partial → paid.

> Catatan navigasi: role yang punya akses ke lebih dari satu modul (mis.
> Gudang punya akses SCM & Purchasing) belum ada tombol pindah sidebar
> antar-modul — untuk sekarang harus lewat URL langsung
> (`/purchasing/...`), sama seperti keterbatasan yang sudah ada sebelumnya
> di modul lain.

### Finance — `/finance` — role Admin, Finance
Chart of Accounts, mapping akun jurnal, & laporan keuangan. **Tidak ada input jurnal manual** — semua jurnal dibuat OTOMATIS lewat Observer setiap ada event tertentu, akun debit/kredit-nya diatur di halaman mapping (bukan di-hardcode).
- **Chart of Accounts** (`finance.chart-of-accounts.index`) — baca saja (5 tipe akun: Aset/Liabilitas/Ekuitas/Pendapatan/Beban), tampilkan saldo tiap akun.
- **Mapping Akun Jurnal** (`finance.transaction-mappings.*`) — atur akun debit/kredit tiap jenis transaksi (edit saja, bukan create — jenis transaksi tetap 3, sesuai observer yang ada).
- **Auto-posting jurnal** — 3 event: (1) Pengajuan GA berstatus "Diterima" → debit Beban Operasional, kredit Kas; (2) Barang dari supplier diterima (GoodsReceipt, kategori food atau general) → debit Persediaan, kredit Hutang Usaha; (3) Invoice supplier LUNAS penuh (bukan tiap cicilan) → debit Hutang Usaha, kredit Kas.
- **Buku Besar** (`finance.reports.general-ledger`) — mutasi 1 akun dalam periode + saldo berjalan.
- **Beban Operasional & Persediaan** (`finance.reports.expense-inventory`) — per outlet (ditelusuri dari GaRequest/PurchaseOrder terkait) & konsolidasi.
- **Umur Hutang / Aging Payable** (`finance.reports.payables-aging`) — supplier invoice belum lunas, dikelompokkan belum jatuh tempo / 1–30 / 31–60 / >60 hari.
- **Neraca Sederhana** (`finance.reports.balance-sheet`) — saldo akhir tiap akun Aset/Liabilitas/Ekuitas, TANPA closing entry otomatis dari Pendapatan/Beban.

> Kalau mapping akun utk suatu jenis transaksi belum ada (dihapus/belum di-seed), aksi terkait (mis. approve step terakhir Pengajuan GA) akan GAGAL dengan error jelas — bukan lolos tanpa jurnal. Ini kesengajaan (supaya buku tidak pernah diam-diam tidak balance) dan aman: seluruh aksi terkait (bukan cuma jurnalnya) ikut dibatalkan (rollback), jadi tidak ada state rusak — tinggal perbaiki mapping-nya lalu ulangi aksinya.

### IT — `/it` — role IT
- **Kontrol Akses & Mode Pemeliharaan** — nyalakan/matikan status "sedang dalam pemeliharaan" per halaman tanpa perlu deploy ulang.
- **Papan Kerja Kanban** — task IT (bug fix/pengembangan), checklist, komentar, label.
- **Manajemen User** — satu-satunya jalur pembuatan akun baru (`/register` publik sudah dihapus). IT memilih role (dipakai murni untuk approval) DAN daftar modul eksplisit per akun (`module_user`, independen dari role — role cuma dipakai sebagai saran default modul saat bikin akun baru). Password awal diketik manual atau digenerate acak di form (tampil sekali, disampaikan manual lewat WA/Telegram), user wajib ganti sendiri di login pertama. IT juga bisa nonaktifkan akun (`is_active`, ditolak langsung saat login) dan reset password kapan saja.
- **Design System** — halaman referensi visual & komponen inti Allez ERP.

### Umum
- **Profile** — tiap user bisa ubah data akun/password sendiri.

## Alur Kerja per Modul

Ringkasan urutan proses end-to-end tiap modul — siapa mengerjakan apa, setelah apa.

### GA

```
Pengajuan GA:
Staff GA ajukan → Finance "diketahui" (step 1) → Head "disetujui" (step 2)
→ Finance "diterima" (step 3, selesai)
Status: Diajukan → Dalam Review → Disetujui → Diterima (Ditolak bisa terjadi di step mana pun)
```

- **Inventaris Aset** — tanpa approval. GA CRUD aset kapan saja; status (Bagus/Dalam Pemeliharaan/Rusak) cuma penanda kondisi, bukan alur berjenjang.
- **Inventaris Seragam** — tanpa approval. restock (tambah stok) / issue (keluarkan ke karyawan → catatan "Dipakai") / adjustment (koreksi manual) / disposal (buang stok rusak); karyawan mengembalikan → catatan ditandai "Dikembalikan".
- **Jadwal Pemeliharaan** — tanpa approval. GA jadwalkan pekerjaan pada aset → kerjakan → tandai selesai + catatan penyelesaian.
- **Work Log** — tanpa approval. Isi form (tanggal/waktu, kategori, outlet, teknisi, detail/hasil pengerjaan, foto) → langsung tersimpan & muncul di daftar. Murni catatan kontrol kinerja teknisi, tidak terhubung ke Aset/Jadwal Pemeliharaan manapun.

### Head

- **Approval Inbox** — satu halaman generik menampung SEMUA item yang gilirannya Head approve, lintas modul apa pun yang pakai trait `Approvable` (Pengajuan GA step 2, PO step Head, dst) — approve/reject di sana, bukan di halaman masing-masing modul.
- **Kontrol Modul** — aktif/nonaktifkan modul GA/SCM/Purchasing & atur role yang boleh akses per modul.
- **Monitoring** (Aset/Seragam/Pemeliharaan/SCM) — read-only murni, tidak ada aksi approve/edit di halaman ini.

### SCM (Supply Chain / Warehouse)

```
Produksi ajukan Pengajuan Bahan → Admin approve (1 langkah)
→ Produksi buat Batch Produksi dari pengajuan yang disetujui (bisa multi-produk)
→ Admin approve (1 langkah)
→ Gudang cetak Label (QR + ED opsional → stok OTOMATIS bertambah di Gudang)
→ Gudang buat & kirim Surat Jalan ke Outlet (wajib foto, item dipilih otomatis FEFO
   → stok berkurang dari Gudang saat status jadi "sent")
→ Outlet konfirmasi Terima (wajib foto, isi qty diterima → stok bertambah di Outlet)
→ Kalau qty diterima ≠ qty dikirim: Laporan Selisih dibuat OTOMATIS (bukan approval,
   murni observer)
```

### Purchasing

```
FOOD (bahan makanan):
Outlet ajukan Purchase Requisition (nama+qty saja)
→ Purchasing approve/reject (1 langkah)
→ [kalau approved] Purchasing buat PO sesungguhnya (pilih supplier, tujuan
   pengiriman — biasanya Central Kitchen/Storage, & isi harga per item)
→ Head approve (lewat Approval Inbox) → Finance approve ("pencairan dana")
→ Gudang/GA konfirmasi terima barang (wajib foto → stok OTOMATIS bertambah)
→ Finance catat Invoice Supplier → Finance catat pembayaran (unpaid → partial → paid)

GENERAL (barang umum):
GA buat PO langsung (TANPA requisisi)
→ Purchasing approve (step tambahan, krn belum pernah direview lewat requisisi)
→ Head approve → Finance approve
→ Gudang/GA konfirmasi terima barang (tercatat sbg bukti, stok TIDAK disentuh)
→ Invoice & pembayaran sama seperti food
```

### Finance

```
Tidak ada input manual — jurnal ter-post OTOMATIS lewat Observer tiap kali:

Pengajuan GA jadi "Diterima" (step 3 Finance selesai)
→ jurnal: debit Beban Operasional, kredit Kas (sebesar total_amount)

GoodsReceipt tersimpan (barang dari supplier diterima, kategori apa pun)
→ jurnal: debit Persediaan, kredit Hutang Usaha (sebesar qty x harga/unit)

SupplierInvoice status jadi "paid" PENUH (bukan tiap cicilan)
→ jurnal: debit Hutang Usaha, kredit Kas (sebesar total invoice)

Semua akun debit/kredit di atas diambil dari halaman Mapping Akun Jurnal
(Admin/Finance bisa ubah tanpa sentuh kode). Kalau mapping belum ada →
aksi terkait GAGAL (rollback total), bukan lolos tanpa jurnal.
```

### IT

- **Kontrol Akses & Mode Pemeliharaan** — IT tandai satu halaman tertentu "Dalam Pemeliharaan" (per key `SystemModule`) → user non-IT/Admin yang akses halaman itu otomatis dialihkan ke halaman notice pemeliharaan → IT matikan status itu kalau perbaikan selesai.
- **Papan Kerja Kanban** — buat task → pindah kolom (drag & drop status) → tambah checklist/komentar → selesai.
- **Manajemen User** — IT isi nama/email/divisi/branch → pilih role (checkbox multi, cuma dipakai untuk approval) → checklist modul auto-tercentang sesuai default role yang dipilih (bisa diubah manual, ini yang disimpan) → isi password (ketik manual/generate acak, tampil sekali) → simpan → user wajib ganti password sendiri di login pertama. Edit: ubah role/modul/branch/divisi & toggle aktif-nonaktif kapan saja (independen satu sama lain); tombol Reset Password generate password baru + paksa ganti lagi.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
