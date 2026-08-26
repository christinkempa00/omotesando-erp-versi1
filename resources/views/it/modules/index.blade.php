<x-app-layout sidebar="it">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kontrol Modul — Mode Pemeliharaan
        </h2>
    </x-slot>

    <div class="font-it py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <p class="text-sm text-gray-500">
                Tandai halaman tertentu sebagai "Dalam Pemeliharaan" — role selain IT/Admin yang mencoba
                mengakses halaman tersebut akan otomatis diarahkan ke halaman pemberitahuan.
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach ($modules as $module)
                    <div
                        x-data="{
                            underMaintenance: @js($module->is_under_maintenance),
                            note: @js($module->maintenance_note ?? ''),
                            updatedBy: @js($module->updatedBy?->name),
                            updatedAt: @js($module->updated_at->format('d M Y H:i')),
                            showNoteInput: false,
                            saving: false,
                            error: null,
                            toggleUrl: '{{ route('it.modules.toggle', $module) }}',
                            requestToggle() {
                                this.error = null;
                                if (this.underMaintenance) {
                                    this.confirmToggle(false);
                                } else {
                                    this.showNoteInput = true;
                                }
                            },
                            cancelOn() {
                                this.showNoteInput = false;
                                this.note = @js($module->maintenance_note ?? '');
                            },
                            async confirmToggle(turnOn) {
                                if (turnOn && this.note.trim() === '') {
                                    this.error = 'Catatan wajib diisi saat menandai modul dalam pemeliharaan.';
                                    return;
                                }
                                this.saving = true;
                                this.error = null;
                                try {
                                    const res = await fetch(this.toggleUrl, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        },
                                        body: JSON.stringify({
                                            is_under_maintenance: turnOn,
                                            maintenance_note: turnOn ? this.note : null,
                                        }),
                                    });
                                    const data = await res.json();
                                    if (!res.ok) {
                                        this.error = data.message ?? 'Gagal menyimpan perubahan.';
                                        return;
                                    }
                                    this.underMaintenance = data.module.is_under_maintenance;
                                    this.note = data.module.maintenance_note ?? '';
                                    this.updatedBy = data.module.updated_by;
                                    this.updatedAt = data.module.updated_at;
                                    this.showNoteInput = false;
                                } catch (e) {
                                    this.error = 'Gagal menghubungi server.';
                                } finally {
                                    this.saving = false;
                                }
                            },
                        }"
                        class="glass-panel p-5"
                        :class="underMaintenance ? 'border-t-[3px] border-t-amber-500' : 'border-t-[3px] border-t-emerald-500'"
                    >
                        <div class="flex items-start justify-between mb-3 gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800 truncate">{{ $module->name }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $module->key }}</p>
                            </div>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium shrink-0"
                                  :class="underMaintenance ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                                  x-text="underMaintenance ? 'Dalam Pemeliharaan' : 'Aktif'">
                            </span>
                        </div>

                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-600">Mode Pemeliharaan</span>
                            <button type="button" @click="requestToggle()" :disabled="saving"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition disabled:opacity-50"
                                    :class="underMaintenance ? 'bg-amber-500' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                                      :class="underMaintenance ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>

                        <template x-if="error">
                            <p class="text-xs text-red-600 mb-2" x-text="error"></p>
                        </template>

                        {{-- Input catatan — muncul saat mau menyalakan mode pemeliharaan,
                             wajib diisi sebelum dikonfirmasi. --}}
                        <div x-show="showNoteInput" x-cloak class="mb-3 space-y-2 rounded-md bg-amber-50 border border-amber-200 p-3">
                            <label class="block text-xs font-medium text-amber-800">Catatan (alasan/estimasi selesai)</label>
                            <textarea x-model="note" rows="2"
                                      class="w-full text-sm rounded-md border-amber-300 focus:border-amber-500 focus:ring-amber-500"
                                      placeholder="Contoh: Maintenance server, estimasi selesai 15:00"></textarea>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="confirmToggle(true)" :disabled="saving"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50">
                                    Konfirmasi
                                </button>
                                <button type="button" @click="cancelOn()" :disabled="saving"
                                        class="px-3 py-1.5 text-xs font-medium rounded-md bg-white border border-gray-200 text-gray-600 hover:bg-gray-50">
                                    Batal
                                </button>
                            </div>
                        </div>

                        <template x-if="underMaintenance && note && !showNoteInput">
                            <p class="text-xs text-gray-500 mb-2" x-text="'Catatan: ' + note"></p>
                        </template>

                        <p class="flex items-center gap-1.5 text-xs text-gray-400 mt-3 pt-3 border-t border-gray-100" x-show="updatedBy">
                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 3" /></svg>
                            Terakhir diubah oleh <span x-text="updatedBy" class="font-medium text-gray-500"></span> pada <span x-text="updatedAt"></span>
                        </p>

                        @if ($module->linkedTasks->isNotEmpty())
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-xs font-medium text-gray-500 mb-1.5">Task Papan Kerja Terkait</p>
                                <ul class="space-y-1">
                                    @foreach ($module->linkedTasks as $task)
                                        <li class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600 truncate">{{ $task->title }}</span>
                                            <span class="text-gray-400 shrink-0 ml-2">{{ $task->column?->name }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Aktivitas terbaru --}}
            <div class="glass-panel overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-800">Aktivitas Mode Pemeliharaan Terbaru</h3>
                </div>
                <div class="px-6 py-4">
                    @forelse ($recentActivity as $log)
                        <div class="relative pl-6 pb-4 last:pb-0 border-l-2 border-gold-200 last:border-transparent">
                            <span class="absolute -left-[5px] top-1 w-2.5 h-2.5 rounded-full bg-gold-500 ring-4 ring-white"></span>
                            <div class="flex items-center justify-between text-sm">
                                <div class="min-w-0">
                                    <p class="text-gray-800 truncate">{{ $log->description }}</p>
                                    <p class="text-xs text-gray-400">oleh {{ $log->user?->name ?? 'Sistem' }}</p>
                                </div>
                                <span class="text-xs text-gray-400 shrink-0">{{ $log->created_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-500 py-4">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
