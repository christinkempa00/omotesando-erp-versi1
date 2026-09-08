<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.uniforms.records.show', $record)" parent="Detail Serah Terima" current="Edit Serah Terima" />
            <h2 class="font-semibold text-xl text-ink leading-tight">Edit Serah-terima {{ $record->record_code }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="card-flat p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ga.uniforms.records.update', $record) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Nama Karyawan *</label>
                        <input type="text" name="employee_name" required value="{{ old('employee_name', $record->employee_name) }}"
                               class="w-full field-input">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Nama Penyerah *</label>
                        <input type="text" name="issued_by_name" required value="{{ old('issued_by_name', $record->issued_by_name) }}"
                               class="w-full field-input">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Seragam</label>
                        <p class="text-sm text-ink-muted">{{ $record->summaryLabel() }}</p>
                        <p class="text-xs text-ink-muted mt-1">Item tidak bisa diganti setelah dibuat. Hapus &amp; buat ulang kalau salah pilih.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Tanggal Serah *</label>
                        <input type="date" name="issue_date" required
                               value="{{ old('issue_date', $record->issue_date->format('Y-m-d')) }}"
                               class="w-full field-input">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Catatan</label>
                        <textarea name="issue_notes" rows="2"
                                  class="w-full rounded-lg border-hairline focus:border-action focus:ring-action">{{ old('issue_notes', $record->issue_notes) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('ga.uniforms.records.show', $record) }}" class="btn-secondary">Batal</a>
                        <button type="submit" class="btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
