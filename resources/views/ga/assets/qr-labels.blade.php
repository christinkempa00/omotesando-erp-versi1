<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between">
            <div>
                <x-back-link :href="route('ga.assets.index')" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cetak Label QR ({{ $assets->count() }} aset)
                </h2>
            </div>
            <div class="print:hidden flex items-center gap-3">
                <select id="qr-size-select" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                <button type="button" id="qr-download-all-btn" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700">
                    Download Semua (PNG)
                </button>
            </div>
        </div>
        <p id="qr-download-progress" class="print:hidden text-xs text-gray-400 mt-2"></p>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 print:max-w-none print:px-0">
            @if ($assets->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-400">
                    Tidak ada aset yang cocok untuk dicetak labelnya.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 print:grid-cols-2 print:gap-2">
                    @foreach ($assets as $asset)
                        <div class="bg-white shadow-sm rounded-lg p-4 flex items-center gap-4 break-inside-avoid print:border print:border-gray-300 print:shadow-none">
                            <div class="w-20 h-20 shrink-0 overflow-hidden [&>svg]:w-full [&>svg]:h-full">{!! $qrSvgs[$asset->id] !!}</div>
                            <div class="min-w-0 text-left">
                                <p class="font-bold text-sm text-gray-900 uppercase leading-tight truncate">{{ $asset->branch?->name }}</p>
                                @if ($asset->branchLocation)
                                    <p class="font-medium text-[11px] text-gray-500 uppercase leading-tight truncate">{{ $asset->branchLocation->name }}</p>
                                @endif
                                <p class="font-bold text-xs text-gray-800 uppercase leading-tight truncate">{{ \Illuminate\Support\Str::limit($asset->name, 28) }}</p>
                                @if ($asset->location)
                                    <p class="font-bold text-[11px] text-gray-600 uppercase leading-tight truncate">{{ $asset->location }}</p>
                                @endif
                                <div class="border-t border-gray-200 my-1.5"></div>
                                <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">Nomor Serial</p>
                                <p class="font-bold text-xs text-gray-900 truncate">{{ $asset->serial_number ?: $asset->asset_code }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <style>
        @media print {
            nav, header, .print\:hidden { display: none !important; }
        }
        .break-inside-avoid { break-inside: avoid; page-break-inside: avoid; }
    </style>

    @include('ga.assets.partials._qr-canvas-js')
    <script>
        const QR_ASSETS_DATA = {!! Illuminate\Support\Js::from($assets->map(fn ($a) => [
            'code' => $a->asset_code,
            'serial' => $a->serial_number,
            'name' => $a->name,
            'branch' => optional($a->branch)->name,
            'cabang' => optional($a->branchLocation)->name,
            'location' => $a->location,
            'qr' => $qrPngDataUris[$a->id],
        ])->values()) !!};

        document.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('qr-size-select');
            populateQrSizeSelect(select);

            const btn = document.getElementById('qr-download-all-btn');
            const progress = document.getElementById('qr-download-progress');
            if (!btn) return;

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                for (let i = 0; i < QR_ASSETS_DATA.length; i++) {
                    const asset = QR_ASSETS_DATA[i];
                    progress.textContent = `Mengunduh ${i + 1} / ${QR_ASSETS_DATA.length}...`;
                    const canvas = await buildQrLabelCanvas(asset, select.value);
                    downloadCanvasAsPng(canvas, `QR-${asset.code}-${select.value}.png`);
                    await new Promise(r => setTimeout(r, 350));
                }
                progress.textContent = `Selesai: ${QR_ASSETS_DATA.length} gambar diunduh.`;
                btn.disabled = false;
            });
        });
    </script>
</x-app-layout>
    