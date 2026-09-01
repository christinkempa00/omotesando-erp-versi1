@props([
    'name' => 'signature',
    'savedSignatureUrl' => null,
    'label' => 'Tanda Tangan',
    'showSaveAsDefaultOption' => false,
    'required' => true,
])

@once
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
@endonce

<div
    x-data="{
        mode: '{{ $savedSignatureUrl ? 'saved' : 'draw' }}',
        pad: null,
        errorMessage: '',
        initPad() {
            const canvas = this.$refs.canvas;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            this.pad = new SignaturePad(canvas, { penColor: '#111827' });
            this.pad.addEventListener('endStroke', () => this.syncDataInput());
        },
        switchToDraw() {
            this.mode = 'draw';
            this.errorMessage = '';
            this.$refs.useSavedInput.value = '0';
            this.$nextTick(() => { if (! this.pad) { this.initPad(); } });
        },
        switchToSaved() {
            this.mode = 'saved';
            this.errorMessage = '';
            this.$refs.dataInput.value = '';
            this.$refs.useSavedInput.value = '1';
        },
        clearPad() {
            if (this.pad) { this.pad.clear(); }
            this.$refs.dataInput.value = '';
            this.errorMessage = '';
        },
        syncDataInput() {
            this.$refs.dataInput.value = (this.pad && ! this.pad.isEmpty()) ? this.pad.toDataURL('image/png') : '';
        },
        validateOnSubmit(event) {
            if (! {{ $required ? 'true' : 'false' }}) {
                return true;
            }
            // Tombol submit bernilai 'draft' (konvensi form 2-tombol
            // Simpan Draft / Kirim) sengaja melewati validasi ini sama
            // sekali — tanda tangan cuma wajib pas benar-benar dikirim.
            if (event.submitter && event.submitter.value === 'draft') {
                return true;
            }
            if (this.mode === 'saved') {
                return true;
            }
            this.syncDataInput();
            if (! this.pad || this.pad.isEmpty()) {
                this.errorMessage = 'Tanda tangan wajib diisi, gambar dulu di area kanvas{{ $savedSignatureUrl ? ", atau pilih pakai tanda tangan tersimpan" : "" }}.';
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            }
            return true;
        },
    }"
    x-init="
        if (mode === 'draw') { $nextTick(() => initPad()); }
        $el.closest('form').addEventListener('submit', (e) => validateOnSubmit(e), true);
    "
    class="space-y-2"
>
    <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>
        <div class="flex items-center gap-3 text-xs font-medium">
            @if ($savedSignatureUrl)
                <button type="button" @click="switchToSaved()" :class="mode === 'saved' ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-600'">
                    Gunakan Tersimpan
                </button>
                <button type="button" @click="switchToDraw()" :class="mode === 'draw' ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-600'">
                    Gambar Baru
                </button>
            @endif
            <button type="button" x-show="mode === 'draw'" @click="clearPad()" class="text-gray-500 hover:text-gray-700">
                Hapus
            </button>
        </div>
    </div>

    @if ($savedSignatureUrl)
        <div x-show="mode === 'saved'" class="border border-gray-300 rounded-md bg-white p-2 flex items-center justify-center" style="height: 160px;">
            <img src="{{ $savedSignatureUrl }}" alt="Tanda tangan tersimpan" class="max-h-full object-contain">
        </div>
    @endif

    <div x-show="mode === 'draw'">
        <canvas x-ref="canvas"
                class="w-full border border-gray-300 rounded-md bg-white touch-none" style="touch-action: none; height: 160px;"></canvas>
    </div>

    @if ($showSaveAsDefaultOption)
        <label x-show="mode === 'draw'" class="flex items-center gap-2 text-xs text-gray-600">
            <input type="checkbox" name="{{ $name }}_save_as_default" value="1"
                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Simpan sebagai tanda tangan default (dipakai otomatis lain kali)
        </label>
    @endif

    <input type="hidden" name="{{ $name }}_data" x-ref="dataInput">
    <input type="hidden" name="{{ $name }}_use_saved" value="{{ $savedSignatureUrl ? '1' : '0' }}" x-ref="useSavedInput">

    <p class="text-xs text-gray-400">
        Tanda tangan gambar ini bukan tanda tangan elektronik tersertifikasi, hanya ditempel ke dokumen PDF sebagai jejak audit internal.
    </p>
    <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-600 font-medium"></p>
</div>
