# Panduan Deploy ke Hostinger (portal.allezgroup.com)

Checklist singkat, ikuti berurutan dari atas ke bawah.

## 1. Persiapan di komputer lokal (SEBELUM upload)

- [ ] Jalankan `composer install --no-dev --optimize-autoloader` — supaya
      folder `vendor/` lengkap & siap diupload (shared hosting tanpa SSH
      biasanya tidak bisa jalankan `composer install` sendiri).
- [ ] Jalankan `npm run build` — hasil compile CSS/JS masuk ke
      `public/build/`, ini yang diupload, BUKAN folder `node_modules/`.
- [ ] Generate `APP_KEY` baru khusus production:
      `php artisan key:generate --show` — simpan hasilnya, JANGAN pakai
      APP_KEY dari `.env` lokal Anda.
- [ ] Salin `.env.production.example` jadi `.env` (file baru, lokal dulu),
      isi semua baris yang ditandai komentar "ISI MANUAL" di dalamnya:
      `APP_KEY` (dari langkah di atas), `DB_*` (dari hPanel Hostinger →
      menu Databases), dan `DEPLOY_TOKEN` (HANYA kalau nanti ternyata
      hosting tidak ada akses Terminal — lihat langkah 4b).

## 2. Buat database MySQL di Hostinger

- [ ] Di hPanel Hostinger: menu **Databases** → buat database baru + user
      + password. Catat nama database, username, password, host (biasanya
      `localhost`).
- [ ] Isi 5 nilai itu ke `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`,
      `DB_PASSWORD` di `.env` yang sudah disiapkan di langkah 1.

## 3. Upload file ke server

Upload semua isi project **KECUALI**:
- `node_modules/` (tidak dipakai di production, isinya besar & tidak perlu)
- `.git/` (riwayat git, tidak perlu di server)
- `database/database.sqlite` (file dev lokal, production pakai MySQL)
- `.env` lokal Anda yang lama — upload `.env` yang SUDAH diisi sesuai
  langkah 1, bukan yang dipakai untuk development.
- `tests/` (opsional, boleh ikut atau tidak — tidak dipakai runtime)

Cara upload: File Manager hPanel (upload .zip lalu extract di server), atau
FTP/SFTP kalau tersedia. Root Laravel (folder `public/`) harus jadi
**document root** domain `portal.allezgroup.com` — kalau hosting tidak
mendukung pengaturan document root custom, lihat catatan di bagian 6.

## 4. Jalankan migrate & storage:link

**4a. Kalau hosting ADA akses Terminal/SSH** (cek dulu di hPanel, menu
Advanced → biasanya ada "SSH Access" atau "Terminal"):

```bash
cd path/ke/project
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**4b. Kalau TIDAK ada akses Terminal/SSH** — pakai route darurat:

1. Pastikan `DEPLOY_TOKEN` di `.env` server sudah diisi string acak yang
   panjang (bukan kosong — kalau kosong route ini otomatis 404).
2. Buka `https://portal.allezgroup.com/deploy-tasks/TOKEN_ANDA` di browser
   — **jangan kirim link ini ke WhatsApp/Telegram/chat apa pun** sebelum
   diklik sendiri (aplikasi chat suka auto-fetch link untuk bikin preview,
   itu bisa memicu halaman ini kebuka duluan tanpa sengaja).
3. Halaman akan menampilkan daftar command + 1 tombol **"Jalankan
   Sekarang"** — klik tombol itu (baru di titik ini command benar-benar
   jalan, bukan saat halaman kebuka).
4. Tunggu sampai muncul output tiap command (plain text). Pastikan semua
   baris berakhir `(exit code: 0)` — kalau ada yang bukan 0 atau muncul
   "GAGAL: ...", itu perlu diperbaiki (biasanya kredensial DB salah, cek
   ulang `.env`) sebelum lanjut.

## 5. Verifikasi

- [ ] Buka `https://portal.allezgroup.com` — pastikan halaman login tampil
      normal (bukan error 500).
- [ ] Login pakai salah satu akun, cek 1 fitur yang pakai upload foto
      (mis. buat Asset baru) — kalau foto tampil setelah disimpan, berarti
      `storage:link` sudah benar.
- [ ] Cek tidak ada pesan error PHP yang tampil ke layar (kalau `APP_DEBUG`
      sudah `false`, error akan tampil sebagai halaman generik, bukan stack
      trace lengkap — itu tandanya benar).

## 6. Setelah Selesai — WAJIB Matikan Route Darurat

Kalau langkah 4b (route `/deploy-tasks/...`) dipakai:

- **Cara paling aman & pasti:** hapus file `routes/deploy.php` (atau
  kosongkan isinya, sisakan `<?php` saja), lalu upload ulang file itu.
  Cara ini SELALU berhasil menonaktifkan route, apa pun kondisinya.
- **Alternatif lebih cepat (tapi baca catatan di bawah):** kosongkan baris
  `DEPLOY_TOKEN=` di `.env` server (jadi kosong, bukan dihapus barisnya).

  ⚠️ **Catatan penting:** kalau `config:cache` sempat dijalankan (baik lewat
  langkah 4a maupun 4b), Laravel membekukan nilai `DEPLOY_TOKEN` lama ke
  file `bootstrap/cache/config.php` dan berhenti membaca `.env` lagi.
  Artinya sekadar mengosongkan `DEPLOY_TOKEN` di `.env` SETELAH itu **tidak
  langsung** mematikan route-nya. Kalau masih ada akses Terminal, jalankan
  `php artisan config:clear` setelah mengosongkan token. Kalau tidak ada
  akses Terminal sama sekali, hapus file `bootstrap/cache/config.php` lewat
  File Manager — atau langsung pakai cara pertama (hapus `routes/deploy.php`).

Jangan biarkan route ini aktif permanen di production — ini murni alat
bantu sekali pakai untuk deploy pertama.
