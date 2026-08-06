<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.uniforms.records.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Serah-terima Seragam Baru</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($branches->isEmpty())
                    <p class="text-sm text-gray-500">
                        Tidak ada varian seragam dengan stok tersedia. Restock dulu di
                        <a href="{{ route('ga.uniforms.stocks.index') }}" class="text-indigo-600 hover:underline">halaman Stok</a>.
                    </p>
                @else
                    <form method="POST" action="{{ route('ga.uniforms.records.store') }}" enctype="multipart/form-data" class="space-y-5"
                          x-data="{
                              tree: {{ Illuminate\Support\Js::from($stockTree) }},
                              branchId: '{{ old('branch_id') }}',
                              type: '{{ old('type') }}',
                              color: '{{ old('color') }}',
                              stockId: '{{ old('uniform_stock_id') }}',
                              get types() { return this.branchId ? Object.keys(this.tree[this.branchId] || {}) : []; },
                              get colors() { return (this.branchId && this.type) ? Object.keys((this.tree[this.branchId] || {})[this.type] || {}) : []; },
                              get sizes() { return (this.branchId && this.type && this.color) ? ((this.tree[this.branchId] || {})[this.type] || {})[this.color] || [] : []; },
                              signaturePad: null,
                              initSignaturePad() {
                                  const canvas = this.$refs.signatureCanvas;
                                  const ctx = canvas.getContext('2d');
                                  ctx.lineWidth = 2;
                                  ctx.lineCap = 'round';
                                  ctx.strokeStyle = '#111827';
                                  let drawing = false;

                                  const pos = (e) => {
                                      const rect = canvas.getBoundingClientRect();
                                      const point = e.touches ? e.touches[0] : e;
                                      return { x: point.clientX - rect.left, y: point.clientY - rect.top };
                                  };
                                  const start = (e) => { drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); };
                                  const move = (e) => { if (!drawing) return; e.preventDefault(); const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); };
                                  const end = () => { drawing = false; };

                                  canvas.addEventListener('mousedown', start);
                                  canvas.addEventListener('mousemove', move);
                                  window.addEventListener('mouseup', end);
                                  canvas.addEventListener('touchstart', start);
                                  canvas.addEventListener('touchmove', move);
                                  canvas.addEventListener('touchend', end);
                              },
                              clearSignature() {
                                  const canvas = this.$refs.signatureCanvas;
                                  canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                              },
                              submitForm() {
                                  const canvas = this.$refs.signatureCanvas;
                                  const blank = document.createElement('canvas');
                                  blank.width = canvas.width;
                                  blank.height = canvas.height;
                                  if (canvas.toDataURL() !== blank.toDataURL()) {
                                      this.$refs.signatureData.value = canvas.toDataURL('image/png');
                                  }
                              },
                          }"
                          @submit="submitForm()">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Karyawan *</label>
                            <input type="text" name="employee_name" required value="{{ old('employee_name') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                            <p class="text-sm font-medium text-gray-700">Varian Seragam *</p>
                            <p class="text-xs text-gray-400 -mt-2">Pilih outlet dulu, lalu jenis seragam, warna, dan ukuran akan menyesuaikan.</p>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Outlet</label>
                                <select x-model="branchId" @change="type = ''; color = ''; stockId = ''"
                                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Pilih Outlet --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Jenis Seragam</label>
                                <select x-model="type" @change="color = ''; stockId = ''" :disabled="! branchId"
                                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="">-- Pilih Jenis Seragam --</option>
                                    <template x-for="t in types" :key="t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Warna</label>
                                <select x-model="color" @change="stockId = ''" :disabled="! type"
                                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="">-- Pilih Warna --</option>
                                    <template x-for="c in colors" :key="c">
                                        <option :value="c" x-text="c"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Ukuran</label>
                                <select name="uniform_stock_id" x-model="stockId" required :disabled="! color"
                                        class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="">-- Pilih Ukuran --</option>
                                    <template x-for="s in sizes" :key="s.id">
                                        <option :value="s.id" x-text="'Size ' + s.size + ' (' + s.available + ' tersedia)'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Serah *</label>
                            <input type="date" name="issue_date" required value="{{ old('issue_date', now()->toDateString()) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Serah Terima *</label>
                            <input type="file" name="issue_photo" accept="image/*" required
                                   class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p class="text-xs text-gray-400 mt-1">Wajib — bukti foto saat barang/seragam diambil karyawan.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">Tanda Tangan (Karyawan)</label>
                                <button type="button" @click="clearSignature()" class="text-xs font-medium text-gray-500 hover:text-gray-700">
                                    Bersihkan
                                </button>
                            </div>
                            <canvas x-ref="signatureCanvas" x-init="initSignaturePad()" width="500" height="160"
                                    class="w-full border border-gray-300 rounded-md bg-white touch-none" style="touch-action: none;"></canvas>
                            <p class="text-xs text-gray-400 mt-1">Tanda tangan langsung di area ini menggunakan mouse/jari.</p>
                            <input type="hidden" name="signature_data" x-ref="signatureData">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="issue_notes" rows="2"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('issue_notes') }}</textarea>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('ga.uniforms.records.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
