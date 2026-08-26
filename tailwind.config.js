import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Closure warna OKLCH yang mendukung modifier opacity Tailwind (mis.
 * `border-accent/40`) — HARUS bentuk function (bukan string statis) supaya
 * Tailwind bisa suntik {opacityValue} ke channel alpha OKLCH. String polos
 * bikin JIT diam-diam tidak generate kelas `/NN`-nya sama sekali.
 */
const oklch = (l, c, h) => ({ opacityValue }) =>
    opacityValue === undefined ? `oklch(${l} ${c} ${h})` : `oklch(${l} ${c} ${h} / ${opacityValue})`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Badge-color helpers (mis. GaRequest::statusBadgeColor) return
        // Tailwind class strings from PHP match() expressions — perlu di-scan
        // di sini juga, tidak cuma di resources/views, supaya kelasnya
        // ke-generate (bukan cuma dipakai lewat variable saat runtime).
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                // Scoped to the IT module only (`font-it`) — mirrors the
                // look of the native PHP IT system. Loaded via @import in
                // it/partials/sidebar.blade.php since the shared
                // layouts/app.blade.php <head> can't be touched for this.
                // Global `font-sans` above stays Plus Jakarta Sans.
                it: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // IT-module-only accent ("gold/glass" look ported from the
                // native PHP IT system) — additive alongside ink/accent
                // above, not a replacement. Only used inside
                // resources/views/it/**.
                gold: {
                    50: '#fdf8ec',
                    100: '#faefd0',
                    200: '#f4dfa3',
                    300: '#ecc96c',
                    400: '#e0b23e',
                    500: '#c89b2c',
                    600: '#a67f20',
                    700: '#85661a',
                    800: '#6b5216',
                    900: '#574314',
                    950: '#2e230a',
                },
                // Allez ERP Redesign tokens — warm neutral ink/surface with a
                // single terracotta accent, replacing the generic indigo/gray
                // Tailwind defaults used until now. See
                // .design-sync/NOTES.md for the source design (claude.ai/design
                // project "Allez Group ERP Redesign").
                ink: {
                    DEFAULT: oklch('23%', '0.02', '75'),
                    muted: oklch('50%', '0.02', '75'),
                },
                accent: {
                    DEFAULT: oklch('58%', '0.14', '40'),
                    dark: oklch('48%', '0.13', '40'),
                    tint: oklch('95%', '0.03', '40'),
                },
                brass: oklch('64%', '0.09', '85'),
                hairline: oklch('90%', '0.012', '75'),
                surface: oklch('98%', '0.006', '75'),
                // Kontrak warna status — 5 status baku, SAMA di semua modul
                // (GA/SCM/IT/Head) supaya user tidak belajar ulang arti warna
                // tiap pindah modul. Dipakai lewat *::statusBadgeColor().
                status: {
                    pending: { fg: oklch('52%', '0.13', '75'), bg: oklch('94%', '0.05', '80') },
                    approved: { fg: oklch('45%', '0.12', '150'), bg: oklch('94%', '0.05', '150') },
                    rejected: { fg: oklch('50%', '0.17', '25'), bg: oklch('94%', '0.06', '25') },
                    received: { fg: oklch('48%', '0.11', '235'), bg: oklch('94%', '0.04', '235') },
                    discrepancy: { fg: oklch('48%', '0.15', '320'), bg: oklch('94%', '0.05', '320') },
                },
            },
        },
    },

    plugins: [forms],
};
