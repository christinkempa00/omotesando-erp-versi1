# Allez ERP Redesign — reference notes

Source: Claude Design project **"Allez Group ERP Redesign"**
(`https://claude.ai/design/p/2c2b831c-30c1-40a9-8f6b-731df4d30380`, file
`Allez ERP Redesign.dc.html`). This is a **regular Claude Design project**
(`PROJECT_TYPE_PROJECT`), not a design-system project — the `/design-sync`
skill doesn't apply here (this repo has no component library/Storybook to
sync). This reskin was done manually by reading the `.dc.html` mockup via
`DesignSync(get_file)` and translating its visual language into this repo's
actual Blade/Tailwind code.

## Design tokens (implemented in `tailwind.config.js`)

- Fonts: **Plus Jakarta Sans** (`font-sans`, UI text/headings) + **JetBrains
  Mono** (`font-mono` — use for document numbers, QR codes, and
  tabular/nominal figures in tables so columns align).
- Colors: `ink` / `ink-muted` (text), `accent` / `accent-dark` / `accent-tint`
  (terracotta — the ONE primary-action color, replaces old `indigo-600`),
  `brass` (secondary), `hairline` (borders), `surface` (page background).
- **5-status color contract** — `status.{pending,approved,rejected,received,discrepancy}.{fg,bg}`.
  Same meaning → same color in every module. Consumed via each model's
  `statusBadgeColor()` static helper (see `GaRequest`, `MaterialRequest`,
  `ProductionBatch`, `DeliveryNote`) — never hardcode a status badge color
  inline in a Blade view, add/extend the model helper instead.
- **Gotcha #1**: badge-color helpers return Tailwind class strings from PHP,
  not Blade — `tailwind.config.js` `content` had to add `./app/**/*.php` or
  Tailwind's JIT scanner never sees those class names and silently omits
  them from the compiled CSS (cost a rebuild-and-recheck cycle to catch).
- **Gotcha #2**: opacity modifiers (`border-accent/40`, `text-status-*-fg/90`)
  silently produce NO class at all if a custom color is defined as a plain
  string in `tailwind.config.js`. Tailwind can only inject `{opacityValue}`
  into a color defined as a **function** — see the `oklch(l, c, h)` helper
  at the top of `tailwind.config.js` and how every token uses it. If you add
  a new color token, use that helper (or the same function-closure pattern),
  never a plain `'oklch(...)'` string, or `/NN` opacity suffixes on it will
  quietly do nothing.

## Scope decisions made when starting the reskin (2026-07-30)

- **Order**: foundation (tokens + shared layout shell) before any individual
  module page — i.e. this pass only.
- **Sidebar**: switch from dark `slate-900` to the mockup's light/white
  sidebar, AND change Head's per-role module-disable behavior from
  grayed-out-with-"Nonaktif"-badge to **fully hidden** — both confirmed
  explicitly with the user. This does NOT apply to the separate IT
  maintenance-mode badge (`CheckModuleMaintenance` / `SystemModule`) — that
  feature was explicitly built earlier to KEEP the link clickable with just
  a badge, on purpose; don't conflate the two "module" systems.
- Only **`layouts/sidebar.blade.php`** (the GA/default sidebar) was reskinned
  this pass, as the reference implementation. `head/partials/sidebar.blade.php`,
  `it/partials/sidebar.blade.php`, and `scm/partials/sidebar.blade.php` are
  still on the OLD dark theme — same light-theme + hide-disabled pattern
  needs porting to them next (note: IT/SCM sidebars don't have a
  Module-disable concept the same way GA/Head do — check each one's actual
  nav-item guard logic before assuming the GA diff applies verbatim).

## Done in pass 2 (2026-07-30, same day, continued session)

- **`<x-approval-stepper :approvals="..." direction="vertical|horizontal">`**
  (`resources/views/components/approval-stepper.blade.php`) — the mockup's
  done/current/pending/rejected stepper, now a real reusable component.
  Wired into `ga/requests/show`, `scm/materials/show`, `scm/batches/show`.
  `head/approvals/index.blade.php` history-table badges also switched to
  `status-*` tokens (the pending/approved/rejected inline ones — module-tag
  badges like "GA"/"SCM" deliberately left indigo, that's a different
  concept from status).
- **Mobile-first wizards** for the two photo-required SCM flows, replacing
  the old single-page forms:
  - `scm/deliveries/create.blade.php` — 4-step "Buat Surat Jalan" (outlet →
    items w/ qty +/- steppers → foto wajib dashed/thumbnail pattern →
    review). Single native multipart form, Alpine only controls which step
    `x-show`s; nothing submits until the last step.
  - `scm/deliveries/show.blade.php` "Konfirmasi Terima" section — 3-step
    wizard (hitung barang w/ live mismatch highlight → foto wajib → catatan
    + confirm, with a discrepancy-will-be-auto-created banner when a
    mismatch is detected client-side).
  - **Backend change**: `DeliveryNoteController::store()` now accepts an
    optional `sent_photo` — if present, it creates AND sends in one request
    (extracted a shared `markAsSent()` used by both `store()` and the
    existing separate `send()` action, so a delivery can still be created
    as a bare draft and sent later too — nothing removed, only added).
  - Discrepancy count/badge colors (`scm/deliveries/show`,
    `scm/discrepancies/index`, `head/scm/index`, `scm/reports/index`) all
    switched from ad-hoc `orange-*` to `status-discrepancy-*` tokens.
- **`it/design-system` page** (`DesignSystemController` + `it/design-system/index.blade.php`,
  linked from the IT sidebar) — a live-in-app equivalent of the mockup's own
  "Design System" tab: color palette, 5-status legend, typography specimen,
  KPI card, both stepper variants (rendered via the real component, incl. a
  rejected-flow example), the photo-upload pattern, and a **real** Endroid QR
  (not a placeholder). Point people here instead of back to the Claude
  Design mockup when they ask "what should this look like."
- **Config fix**: converted every custom color in `tailwind.config.js` to
  the `oklch(l, c, h)` function-closure helper (see Gotcha #2 above) —
  needed for the wizards' `/40`, `/30`, `/90` opacity modifiers to work at
  all. Do this for any new token from here on.

## Still pending (not started)

- Port the light-sidebar treatment to Head/IT/SCM sidebars (still dark
  `slate-900`; only `layouts/sidebar.blade.php` — the GA one — was redone).
- Reskin remaining individual module pages (GA/Head/IT/SCM index/show/create
  views not touched above) — page headings, tables, cards, buttons mostly
  still use old `indigo-600`/`gray-*` Tailwind defaults, not the new tokens.
- QR-code visual treatment (dark square card + code text underneath, per
  the mockup) not applied to `scm/batches/labels-print.blade.php` or the
  delivery/document PDFs — those still just embed the raw Endroid SVG with
  no surrounding card styling. (The Design System page's QR demo card
  shows what this should look like.)
- Document/PDF templates (Surat Jalan, Bukti Pengambilan Bahan, Berita
  Acara, GAR) don't yet match the mockup's printed-document look (logo+company
  left / doc title+number right header, signature-line footer with QR) —
  they're readable but not restyled.
- Fonts/tokens only added to `layouts/app.blade.php` + `layouts/guest.blade.php`.
  PDF templates use their own inline `<style>` blocks (Dompdf can't load
  Google Fonts reliably) — leave those as-is unless asked, they're a
  print-document context not a UI screen.
- The Surat Jalan wizard doesn't collect `unit_price` per item (dropped to
  keep the mobile flow "minim ketik" per the mockup's own philosophy) — if
  Finance/COGS work later needs it filled at send time rather than
  backfilled, that's a deliberate gap to revisit, not an oversight.
