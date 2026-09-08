# Design System — Allez Group ERP

Sumber kebenaran tunggal untuk pola tampilan yang sudah diterapkan & terbukti
di modul **GA (General Affairs)**. Dipakai untuk (a) menyelaraskan modul lain
(Head/Outlet/IT) ke pola yang sama, dan (b) membangun modul/halaman **baru**
agar konsisten sejak awal — tanpa perlu restyle ulang nanti.

Setiap aturan di bawah mengutip class/token **nyata** yang sudah dipakai di
repo (bukan deskripsi abstrak) — rujukannya adalah `tailwind.config.js`,
`resources/css/app.css`, `resources/views/components/*.blade.php`, dan file
GA yang sudah direstyle penuh (mis. `ga/assets/index.blade.php`,
`ga/dashboard.blade.php`, `ga/requests/show.blade.php`).

---

## 1. Prinsip Umum

- **Light theme, warm-neutral.** Ground/permukaan pakai `surface` (oklch
  hampir putih, warm-neutral) & `ink`/`ink-muted` (oklch warm-gray gelap),
  bukan `gray`/`slate` default Tailwind.
- **Aksi utama = BIRU `action`** (`#2B5C8A`, hover `#234B71`), BUKAN gold.
  Semua tombol/link yang men-trigger aksi (submit, "Terapkan Filter", pill
  filter aktif, tombol edit/detail) pakai token `action`.
- **Gold** = aksen brand + state sidebar aktif + ikon dekoratif stat card +
  panel ringkasan finansial (pengecualian sadar, lihat §1.1). Gold **BUKAN**
  warna tombol/link biasa — kalau menemukan `btn-gold`/`text-gold-*` di link
  aksi biasa pada modul GA, itu peninggalan lama yang harus diselaraskan ke
  `action`, kecuali termasuk salah satu pengecualian di bawah.
- **Warna semantik status** (hijau/kuning/merah, badge status/prioritas,
  indikator low-stock, dll) **JANGAN pernah diubah** saat restyle — itu bagian
  dari makna data, bukan dekorasi. Lihat §5.3.

### 1.1 Pengecualian gold yang disetujui (jangan "perbaiki" balik ke biru)

Tiga pemakaian gold di luar sidebar-active yang **sengaja** dipertahankan:

1. **Ikon kotak dekoratif di stat card dashboard** — `bg-gold-500` solid
   (`ga/dashboard.blade.php`, lihat §4).
2. **Panel ringkasan finansial** (Sub Total/Diskon/PPH/Total) di form
   Pengajuan GA — `border-gold-200 bg-gold-50`, angka Total `text-gold-600`,
   link "+ Diskon"/"+ PPH" `text-gold-600` (`ga/requests/_form.blade.php`).
   Ini pengecualian sadar yang sudah disetujui, bukan sisa restyle yang belum
   selesai.
3. **Avatar inisial user di sidebar** — `bg-gradient-to-br from-gold-400
   to-gold-600` (`layouts/sidebar.blade.php`).

Modul **IT** juga masih pakai tema gold/glass penuh (`.glass-panel`,
`.btn-gold`) — itu tema modulnya sendiri, di luar cakupan dokumen ini, TIDAK
untuk diselaraskan ke `card-flat`/`btn-primary`.

---

## 2. Token & Utility (kutip dari `tailwind.config.js` & `app.css`)

### 2.1 Warna (`tailwind.config.js`)

| Token | Nilai | Pemakaian |
|---|---|---|
| `action` / `action.DEFAULT` | `#2B5C8A` | Tombol/link aksi utama, border focus |
| `action.hover` | `#234B71` | Hover state tombol/link action |
| `ink` (`ink.DEFAULT`) | `oklch(23% 0.02 75)` | Teks utama, nilai data |
| `ink-muted` (`ink.muted`) | `oklch(50% 0.02 75)` | Label kecil, teks sekunder, placeholder ikon |
| `hairline` | `oklch(90% 0.012 75)` | SEMUA border (ganti `border-gray-*`) |
| `surface` | `oklch(98% 0.006 75)` | Background hover baris tabel non-sorot, bg input disabled, bg badge netral |
| `gold.50`…`gold.950` | skala emas 11 step | Aksen brand & pengecualian §1.1 (lihat juga `gold.ink` = `#8A6D2B`, teks di atas bg gold terang) |
| `status.pending` / `.approved` / `.rejected` / `.received` / `.discrepancy` | masing-masing `{fg, bg}` oklch | 5-status-baku, lihat §5.3 |
| `accent`, `brass` | oklch, LEGACY | Peninggalan Fase 1, jangan dipakai di kode baru — biarkan bila masih dirujuk view lama yang belum direstyle |

`oklch()` di config ditulis sebagai *function* (`{opacityValue}) => ...`)
supaya modifier opacity Tailwind (`text-ink-muted/60`, `border-status-x-fg/30`)
berfungsi — pola ini WAJIB diikuti kalau menambah token warna oklch baru.

### 2.2 Font (`tailwind.config.js`)

- `font-sans` (default global) → **Inter** — di-load via `@import` di baris
  pertama `app.css`.
- `font-mono` → **JetBrains Mono** — dipakai untuk SEMUA angka/kode: harga,
  ID/kode (`asset_code`, `job_code`, `movement_code`), qty, statistik dashboard,
  info pagination. Kombinasikan dengan `tabular-nums` bila angka berjejer
  (legend donut, kolom angka).

### 2.3 Utility komponen (`app.css`, `@layer components`)

| Class | Definisi inti | Kapan dipakai |
|---|---|---|
| `.card-flat` | `rounded-[14px] border bg-white border-hairline` + shadow tipis | Pengganti `.glass-panel` di modul GA — SEMUA panel/kartu/wrapper tabel |
| `.btn-primary` | `h-10 px-[18px] rounded-lg text-sm font-semibold text-white`, bg `action.DEFAULT`, hover `action.hover` | Tombol aksi utama (submit, "Buat X", "Kirim") |
| `.btn-secondary` | sama tapi `bg-white border-hairline text-ink`, hover `bg-surface` | Tombol sekunder ("Batal", "Export", toggle) |
| `.btn-danger` | sama tapi bg `#B3261E`, hover `#8F1E18` | Aksi destruktif eksplisit (jarang dipakai sbg full button — hapus baris biasanya cukup ikon merah kecil, lihat §5) |
| `.field-input` | `h-10 px-3 rounded-lg border border-hairline` + focus ring `action` | SEMUA `<input>`/`<select>` single-line. Textarea pakai utility yang sama secara eksplisit (`rounded-lg border-hairline focus:border-action focus:ring-action`) TANPA `.field-input` karena class itu fix `h-10` |
| `.sidebar-nav-item` / `.sidebar-nav-item-active` | idle `text-ink hover:bg-surface`; active `bg-gold-500 text-white` | Item nav sidebar, lihat §6 |

`.glass-panel`, `.glass-panel-static`, `.btn-gold`, `.btn-gold-sm`,
`.stat-card`, `.stat-icon` masih ada di `app.css` — itu tema **modul IT**,
JANGAN dihapus, JANGAN dipakai di kode GA/Head/Outlet baru.

---

## 3. Komponen Shared (`resources/views/components/`)

| Komponen | Props inti | Contoh pakai |
|---|---|---|
| `<x-primary-button>` | — (slot) | Wrapper tipis `<button class="btn-primary">`. Untuk link (`<a>`) styled sama, cukup pakai class `btn-primary` langsung. |
| `<x-secondary-button>` | — | `<button class="btn-secondary disabled:opacity-25">` |
| `<x-danger-button>` | — | `<button class="btn-danger">` |
| `<x-text-input>` | `disabled` | `<input class="field-input">` |
| `<x-input-label>` | `value` | `<label class="block font-medium text-sm text-ink">` |
| `<x-filter-bar>` | `action`, `search`, `searchName`, `searchValue`, `searchPlaceholder`, `resetUrl` | Search pill + tombol "Filter" yang buka dropdown panel (slot = field filter tambahan). Lihat §4 INDEX. |
| `<x-filter-pills>` | `name`, `options`, `selected`, `label`, `allLabel` | Radio-as-pill: idle `border-hairline bg-white text-ink-muted`, terpilih `peer-checked:bg-action peer-checked:border-action peer-checked:text-white`. |
| `<x-back-link>` | `href` (wajib), `parent` (opsional), `current` (opsional) | Breadcrumb "‹ Kembali" (default) atau "‹ Parent / Current" bila `parent`/`current` diisi. Lihat §7. |
| `<x-table-footer>` | `paginator` (wajib), `perPageOptions` (default `[10,25,50,100]`) | Footer tabel baku: info "Menampilkan X–Y dari Z" + dropdown per-page + navigasi. Lihat §5.4. |
| `<x-modal>` | `name`, `show`, `maxWidth` | Modal generik Breeze (overlay abu, `bg-white rounded-lg`) — BELUM direstyle ke token GA, dipakai apa adanya. |
| `<x-signature-pad>` | `name`, `savedSignatureUrl`, `label`, `showSaveAsDefaultOption`, `required` | Kanvas tanda tangan (dipakai di form Pengajuan GA & Serah-terima Seragam). **Catatan:** internal komponen ini masih pakai token lama (`text-gray-700`, `text-indigo-600`, `border-gray-300`) — belum direstyle, JANGAN diubah tanpa task terpisah (internal komponen shared di luar guardrail restyle biasa, lihat §8). |
| `<x-donut-chart>` | `data`, `labels`, `colors`, `total`, `size` | Donut generik conic-gradient. Legend kanan sudah `flex-1 min-w-0` (label) + `font-mono tabular-nums` (angka), tapi warna teks (`text-gray-800/600`) belum diselaraskan ke `ink`/`ink-muted` — versi ter-restyle penuh ada di `ga/partials/status-donut.blade.php` (§5.3, dipakai spesifik untuk status Aset/Seragam). |
| `<x-bar-chart>` | `data`, `color` | Bar horizontal CSS. Belum direstyle (masih `text-gray-500/700`, warna default indigo `#6366f1`) — dipakai apa adanya sampai ada task restyle. |
| `<x-ya-tidak-radio>` | `name`, `label`, `selected` | Radio pill Ya/Hijau vs Tidak/Merah — warna semantik (hijau/merah), jangan diubah. |

---

## 4. Pola Halaman (Page Patterns)

### INDEX (daftar/tabel)
Struktur baku (contoh nyata: `ga/assets/index.blade.php`):
```
<x-slot name="header"> judul + tombol "Buat X" (.btn-primary) </x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
  @if(session('success')) ... @endif
  <x-filter-bar :action=... :search-value=... :reset-url=...>
      {{-- field filter tambahan: select outlet, <x-filter-pills>, date range --}}
  </x-filter-bar>
  <div class="card-flat overflow-hidden">
      <div class="overflow-x-auto"><table>...</table></div>  {{-- lihat §5 --}}
  </div>
  <div class="flex ... justify-between">
      <x-table-footer :paginator="$items" />
      {{-- dropdown Export, bila ≥2 format --}}
  </div>
</div></div>
```
**Dropdown Export** (bila ada ≥2 jenis export, mis. xlsx & pdf) — pola
`x-data="{ exportOpen: false }"`, tombol `btn-secondary` berlabel "Export"
dengan ikon panah-turun, dropdown `absolute ... bg-white border-hairline
rounded-lg shadow-lg`, tiap item link ikon berwarna (hijau utk xlsx, merah
utk pdf) — contoh persis di `ga/assets/index.blade.php` baris ~156–183.

### FORM (create/edit)
Struktur baku (contoh nyata: `ga/requests/_form.blade.php`,
`ga/assets/_form.blade.php`):
- Panel `.card-flat p-6` (single panel) atau `grid grid-cols-1 lg:grid-cols-3
  gap-6` dengan kolom kiri `.card-flat` data (2/3 lebar) + kolom kanan
  `.card-flat` foto/ringkasan (1/3 lebar) — pola assets/_form.
- Grid field 2 kolom (`grid grid-cols-1 md:grid-cols-2 gap-4`), tiap field:
  `<label class="block text-sm font-medium text-ink mb-1">` + `.field-input`.
- Textarea: `rounded-lg border-hairline focus:border-action focus:ring-action`
  (tanpa `.field-input`, lihat §2.3).
- Item dinamis (Alpine `x-for`): baris `grid grid-cols-12 gap-2 items-center
  border border-hairline rounded-md p-3 pr-8`, tombol hapus (✕) pojok kanan
  atas `text-red-600 hover:bg-red-50`, tombol tambah "+ Tambah Item"
  `btn-secondary` dengan ikon plus.
- Tombol aksi: `.btn-secondary` ("Batal"/"Draft") + `.btn-primary` (submit
  utama), rata kanan `flex items-center justify-end gap-3`.

### DETAIL (show)
Struktur baku (contoh nyata: `ga/requests/show.blade.php`):
- `<x-back-link>` breadcrumb + judul di header (§7).
- Panel ringkasan `.card-flat p-6`: badge status/prioritas di atas
  (`inline-flex px-3 py-1 rounded-full text-sm font-medium` + badge-color
  helper), lalu `<dl class="grid ... gap-4 text-sm">` dengan `<dt
  class="text-ink-muted">` + `<dd class="text-ink font-medium">`.
- Tabel item (bila ada) pakai standar §5, `<tfoot>` baris subtotal/total
  `bg-surface`.
- Lampiran foto: grid thumbnail `border border-hairline rounded-md`.

### DASHBOARD
Struktur baku (contoh nyata: `ga/dashboard.blade.php`):
- Stat card: `.card-flat flex items-center gap-4 p-6`, ikon
  `w-11 h-11 rounded-[10px] bg-gold-500` (pengecualian §1.1) berisi svg putih,
  angka `font-mono text-[28px] font-medium text-ink`, label
  `text-xs font-medium text-ink-muted`.
- Donut semantik: `@include('ga.partials.status-donut', ...)` — lihat §5.3.
- Badge pill outline ringkasan (mis. status pemeliharaan): `inline-flex
  items-center gap-1.5 h-6 px-2.5 rounded-full border
  border-status-{name}-fg/30 bg-status-{name}-bg text-status-{name}-fg
  text-xs font-semibold`.
- Tabel ringkas (jadwal mendatang, breakdown per outlet) pakai standar §5.

---

## 5. Standar Tabel Baku (wajib identik di semua modul)

Rujukan pasti: `ga/assets/index.blade.php`, `ga/dashboard.blade.php`,
`ga/requests/show.blade.php` — semuanya identik byte-per-byte pada bagian ini.

### 5.1 Struktur

```html
<table class="min-w-full divide-y divide-hairline">
    <thead class="border-b border-hairline">
        <tr>
            <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kolom</th>
            {{-- kolom kosong/ikon/checkbox: sama tapi tanpa uppercase/tracking, cukup text-center --}}
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-hairline">
        <tr class="hover:bg-[#FDFBF4]">
            {{-- atau tambah cursor-pointer + onclick="window.location='...'" kalau seluruh baris klikable --}}
            <td class="px-4 py-3 text-sm text-ink">...</td>
        </tr>
        {{-- @empty: --}}
        <tr><td colspan="N" class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada data.</td></tr>
    </tbody>
</table>
```

Aturan pasti:
- `<thead>` **TIDAK** punya class background (bukan `bg-gray-50`) — cukup
  `border-b border-hairline`.
- Setiap `<th>` isi: `px-4 py-3 text-center text-[11px] font-semibold
  tracking-[0.08em] text-ink-muted uppercase`. `text-center` berlaku untuk
  SEMUA `<th>` (termasuk kolom aksi/ikon) — TAPI `<td>` alignment tetap bebas
  sesuai jenis data (angka/aksi rata kanan `text-right`, teks rata kiri
  default).
- Baris body: `hover:bg-[#FDFBF4]` (bukan `hover:bg-gray-50`/`hover:bg-surface`
  — warna krem hangat spesifik ini adalah standarnya untuk baris List Page).
  Sub-tabel di dalam modal/panel non-List-Page boleh pakai `hover:bg-surface`
  bila `#FDFBF4` terasa terlalu mencolok di konteks kecil.
- `divide-hairline` di `<table>`/`<tbody>` (bukan `divide-gray-200`).
- Nilai teks `text-ink` (utama) / `text-ink-muted` (sekunder, mis. sub-info di
  bawah nilai utama). Kode/ID/angka → tambahkan `font-mono`.

### 5.2 Avatar-inisial (opsional, kolom "penanggung jawab"/teknisi)

`w-7 h-7 rounded-full bg-action text-white text-xs font-semibold
flex items-center justify-center` berisi 1 huruf inisial — dipakai di
`ga/worklogs/index.blade.php` kolom Teknisi in Charge.

### 5.3 Warna semantik (JANGAN DIUBAH)

Dua sistem badge status HIDUP BERDAMPINGAN di kode nyata — keduanya sah,
jangan disatukan paksa:

1. **5-status-baku via token `status.*`** (`GaRequest::statusBadgeColor()`,
   dan badge/ring outline pill di dashboard): `bg-status-{name}-bg
   text-status-{name}-fg`, pill outline tambah `border
   border-status-{name}-fg/30`. Nama: `pending`, `approved`, `rejected`,
   `received`, `discrepancy`.
2. **Palet Tailwind langsung** (`Asset::statusBadgeColor()`,
   `MaintenanceJob::statusBadgeColor()`, `UniformRecord::statusBadgeColor()`):
   `bg-green-100 text-green-700` / `bg-yellow-100 text-yellow-700` /
   `bg-red-100 text-red-700` / `bg-blue-100 text-blue-700`, dst.

Donut status (`ga/partials/status-donut.blade.php`) pakai hex literal
tersendiri (`#1E6B39` bagus, `#eab308` maintenance, `#ef4444` rusak) — bukan
class Tailwind, tapi tetap tergolong warna semantik, JANGAN diubah.

Semua badge di atas dipanggil lewat helper statis model
(`Model::statusBadgeColor($value)` / `priorityBadgeColor()` /
`typeBadgeColor()`) — restyle view TIDAK PERNAH mengubah isi helper ini,
hanya markup di sekitarnya (`class="inline-flex px-2 py-1 text-xs
font-semibold rounded-full {{ Model::statusBadgeColor($x) }}"`).

### 5.4 Footer tabel — `<x-table-footer>`

Ganti SEMUA `{{ $paginator->links() }}` dengan:
```blade
<x-table-footer :paginator="$items" />
```
Komponen ini (lihat §3) menampilkan:
- Teks "Menampilkan **X**–**Y** dari **Z**" (angka `font-mono text-ink`).
- Dropdown "Tampilkan [10/25/50/100]" — submit GET, mempertahankan SEMUA
  query string lain (`request()->except(['per_page','page'])` di-hidden-input
  ulang) supaya filter/search TIDAK hilang saat ganti jumlah baris.
- Navigasi halaman dari `resources/views/vendor/pagination/allez.blade.php`
  — nomor halaman **mengikuti `$elements` bawaan Laravel** (bukan hardcode):
  1 halaman → nav tidak muncul sama sekali (`@if ($paginator->hasPages())`);
  banyak halaman → ellipsis "…" muncul otomatis via `onEachSide(2)`. Style
  tiap item: `min-w-9 h-9 px-2 inline-flex items-center justify-center
  rounded-lg border border-hairline text-sm font-mono hover:bg-surface`,
  aktif → `bg-action text-white border-action`, prev/next disabled →
  `opacity-40 cursor-default pointer-events-none`.
- Seluruh footer disembunyikan (`@if ($paginator->total() > 0)`) kalau data
  kosong.

Controller cukup baca `per_page` dengan whitelist sebelum `->paginate()`:
```php
$perPage = in_array((int) $request->input('per_page'), [10, 25, 50, 100], true)
    ? (int) $request->input('per_page') : 15;
```
lalu `->paginate($perPage)->withQueryString()` (`withQueryString()` WAJIB,
supaya filter ikut terbawa lintas halaman).

---

## 6. Sidebar

Rujukan: `resources/views/layouts/sidebar.blade.php` (dipakai bersama oleh
GA & portal lain lewat class `.sidebar-nav-item`/`.sidebar-nav-item-active`
di `app.css` — markup tiap portal beda file, tapi class-nya SATU definisi).

- Container: `w-64 bg-white border-r border-hairline`.
- Item idle: `.sidebar-nav-item` → `text-ink hover:bg-surface`, ikon svg
  `text-slate-400` (LEGACY token, belum diselaraskan ke `ink-muted` — biarkan
  apa adanya, bukan bug).
- Item aktif (`request()->routeIs($pattern)`): tambah class
  `.sidebar-nav-item-active` → **gold solid** `bg-gold-500 text-white`
  (pengecualian §1.1), ikon ikut putih.
- Section header non-link ("Operational Support"): `text-[11px] font-semibold
  uppercase tracking-wider text-slate-400`.
- Badge "mode pemeliharaan" di item sidebar: `bg-amber-100 text-amber-700`
  (semantik, jangan diubah).
- Avatar user (footer sidebar): `bg-gradient-to-br from-gold-400 to-gold-600`
  (pengecualian §1.1) + inisial 1–2 huruf putih.

---

## 7. Breadcrumb — `<x-back-link>`

```blade
{{-- Default (tanpa breadcrumb bertingkat): --}}
<x-back-link :href="route('ga.requests.index')" />
{{-- tampil: "‹ Kembali" --}}

{{-- Breadcrumb "Parent / Current": --}}
<x-back-link :href="route('ga.requests.index')" parent="Asset Request" current="Buat Pengajuan" />
{{-- tampil: "‹ Asset Request / Buat Pengajuan" --}}
```

Konvensi penamaan yang sudah dipakai di modul GA:
- **create**: parent = nama modul/list (mis. "Asset Request"), current =
  "Buat X".
- **show**: parent = nama modul/list, current = "Detail X".
- **edit**: `:href` biasanya mengarah balik ke **show** (bukan index), maka
  parent = **"Detail X"** (bukan nama modul), current = "Edit X" — supaya
  breadcrumb selalu jujur terhadap kemana tombol itu benar-benar akan
  membawa user. Kalau `:href` edit mengarah ke index (bukan show), parent
  tetap nama modul.
- Kalau ragu suatu halaman butuh label apa (teks tidak jelas/aman), biarkan
  pemanggilan default tanpa `parent`/`current` (tetap tampil "‹ Kembali") —
  jangan memaksakan label yang salah.

---

## 8. Guardrail Restyle (aturan main saat menyentuh view existing)

Berlaku SETIAP kali menyelaraskan halaman lama ke sistem ini:

- **HANYA `class=`** (dan memasang komponen shared seperti
  `<x-table-footer>`/`<x-back-link>` menggantikan markup manual yang setara).
- **DILARANG** mengubah: `name=`/`value=`, semua Alpine (`x-data`, `x-model`,
  `x-show`, `x-for`, `:key`, `:class`, `:value`, `@click`, `@change`,
  `@input`, dst.), `wire:*`, `route()`/argumennya, direktif Blade
  (`@php`/`@if`/`@foreach`/`@forelse`/`@include`/`@csrf`/`@method`), atribut
  `onclick`/`onsubmit`/JS inline lain, `id=` yang dipakai JS (`document.
  getElementById`, SortableJS `handle`, dll), logic `->links()` (diganti
  KOMPONEN, bukan query paginate-nya), struktur/urutan elemen DOM, internal
  komponen shared (kecuali komponen itu sendiri memang jadi target task
  restyle-nya), dan warna semantik (§5.3).
- **JANGAN** menyentuh DB/skema/migration/model logic. Restyle = tampilan
  saja.
- **View dokumen PDF** (`*/document.blade.php`, template cetak) DIKECUALIKAN
  dari restyle — dokumen resmi punya kebutuhan cetak sendiri, bukan bagian
  dari sistem tampilan layar ini.
- Ekspresi ternary Blade di dalam `class=` (mis. `{{ $x->is_low ? 'A' : 'B'
  }}`) — hanya STRING di tiap cabang yang boleh diganti, struktur ternary
  itu sendiri harus utuh byte-per-byte.
- Halaman read-only monitoring (mis. approval Head) — tombol
  approve/reject/logic-nya tidak boleh disentuh sama sekali, walau
  tampilannya ikut diselaraskan.
- Ragu terhadap satu elemen (apakah dia data/logic atau murni tampilan)?
  **STOP, jangan tebak** — laporkan & tanya dulu.

---

## 9. Kamus Find→Replace (restyle modul lama)

| Cari | Ganti dengan |
|---|---|
| `glass-panel` / `glass-panel-static` | `card-flat` |
| `btn-gold`, `btn-gold-sm`, bg indigo pada tombol aksi | `btn-primary` |
| Tombol/link sekunder abu-abu manual (`bg-white border-gray-300 text-gray-700 hover:bg-gray-50`) | `btn-secondary` |
| `text-indigo-600`/`text-indigo-700` (link/tombol aksi, BUKAN badge semantik) | `text-action` / class `btn-primary` |
| `text-gray-700`, `text-gray-800`, `text-slate-700` (teks nilai utama) | `text-ink` |
| `text-gray-400`, `text-gray-500`, `text-slate-400/500` (label kecil/sekunder) | `text-ink-muted` |
| `border-gray-100/200/300`, `border-slate-*` | `border-hairline` |
| `bg-gray-50` (background section/thead) | `bg-surface` ATAU dibuang total (lihat §5.1 — `<thead>` tidak pakai bg sama sekali) |
| `divide-gray-200` | `divide-hairline` |
| `rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500` (input) | class `field-input` (drop semua fragment di kiri) |
| `hover:bg-gray-50` (baris tabel List Page) | `hover:bg-[#FDFBF4]` |
| `shadow-sm` polos di kartu/panel | hapus, ganti wrapper jadi `card-flat` (sudah punya shadow sendiri) |
| Warna badge status/prioritas/tipe (hijau/kuning/merah/biru apa pun bentuknya) | **JANGAN diubah** — itu bukan bagian kamus ini |

Kamus ini untuk kelas **struktural/netral** saja. Setiap kali ragu apakah
sebuah warna itu netral (boleh diganti) atau semantik (jangan diganti), cek
apakah warnanya datang dari helper `*BadgeColor()`/`*LabelColor()` atau
mewakili kondisi data (low-stock, overdue, error) — kalau ya, itu semantik.

---

## 10. Cara Membangun Modul/Halaman Baru (checklist ringkas)

1. **Layout & sidebar**: extend `<x-app-layout>` (atau layout portal yang
   sesuai), tambah entri navigasi di array `$navGa`/`$navOperationalSupport`
   (atau setara di portal lain) — pakai `sidebar-nav-item`, JANGAN bikin
   class baru.
2. **Komponen shared dulu**, baru markup custom: cek §3 sebelum menulis
   `<input>`/`<button>`/breadcrumb/pagination dari nol.
3. **INDEX** baru → ikuti skeleton §4 persis: `<x-filter-bar>` (+
   `<x-filter-pills>` bila ada opsi kategori), `card-flat` wrapper tabel,
   standar tabel §5, `<x-table-footer :paginator="$x" />` di footer.
   Controller: `paginate($perPage)->withQueryString()` dengan whitelist
   `[10,25,50,100]` fallback 15 (§5.4).
4. **FORM** baru → `card-flat p-6`, grid 2 kolom, `field-input`, label
   `text-ink`, tombol `btn-secondary` (batal) + `btn-primary` (submit).
5. **DETAIL** baru → `card-flat p-6` + `<dl>` (dt=`ink-muted`, dd=`ink`),
   breadcrumb `<x-back-link parent="..." current="..." />` sesuai konvensi
   §7.
6. **Badge status baru?** Tambah method `statusBadgeColor()` (atau serupa)
   di model, pilih salah satu dari 2 sistem existing (§5.3) — JANGAN bikin
   sistem warna ketiga tanpa alasan kuat.
7. **Warna aksi** = `action` (biru). **Gold** hanya untuk 3 pengecualian
   §1.1 — kalau halaman baru "terasa butuh" gold di luar itu, itu tanda
   perlu didiskusikan dulu, bukan dieksekusi langsung.
8. Jalankan `npm run build` + `php artisan test` setelah selesai, dan
   verifikasi visual di browser sebelum melapor selesai.

### Skeleton minimal — INDEX

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-ink leading-tight">Judul Modul</h2>
            <a href="{{ route('...create') }}" class="inline-flex items-center btn-primary">Buat X</a>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-filter-bar :action="route('...index')" :search-value="$search" :reset-url="route('...index')" />
            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline">
                        <thead class="border-b border-hairline">
                            <tr><th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Kolom</th></tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-hairline">
                            @forelse ($items as $item)
                                <tr class="hover:bg-[#FDFBF4]"><td class="px-4 py-3 text-sm text-ink">{{ $item->name }}</td></tr>
                            @empty
                                <tr><td class="px-6 py-8 text-center text-sm text-ink-muted">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <x-table-footer :paginator="$items" />
        </div>
    </div>
</x-app-layout>
```

### Skeleton minimal — FORM

```blade
<div class="card-flat p-6 space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-ink mb-1">Nama *</label>
            <input type="text" name="name" required class="w-full field-input">
        </div>
    </div>
    <div class="flex items-center justify-end gap-3">
        <a href="{{ $cancelUrl }}" class="btn-secondary">Batal</a>
        <button type="submit" class="btn-primary">Simpan</button>
    </div>
</div>
```
