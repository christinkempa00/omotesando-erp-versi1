<?php

namespace App\Http\Requests\GA;

use App\Models\GA\GaRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Dipakai utk store() (create baru) & update() (lanjutkan draft) —
 * validasi item/tanda tangan sengaja LEBIH LONGGAR kalau intent=draft
 * (lihat rules() & withValidator()), supaya draft bisa disimpan sebagian
 * jalan dulu & dilengkapi nanti sebelum benar-benar "Kirim Pengajuan".
 */
class StoreGaRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware route sudah membatasi role, jadi di sini cukup true
        // untuk user yang sudah lolos middleware 'role:GA,Head,Admin'.
        return true;
    }

    public function rules(): array
    {
        $isDraft = $this->input('intent') === 'draft';

        return [
            'intent' => ['required', 'in:draft,submit'],
            'requester_name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'category' => ['required', 'in:'.implode(',', array_keys(GaRequest::categoryLabels()))],
            'priority' => ['required', 'in:'.implode(',', array_keys(GaRequest::priorityLabels()))],
            'description' => ['required', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.type' => $isDraft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'items.*.unit' => $isDraft ? ['nullable', 'string', 'max:50'] : ['required', 'string', 'max:50'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price_per_unit' => $isDraft ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'gt:0'],
            'items.*.vendor_name' => $isDraft ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],

            // Lampiran foto: opsional, boleh lebih dari satu.
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['image', 'max:4096'],

            // Tanda tangan pemohon: wajib gambar baru ATAU pakai yang tersimpan
            // (lihat withValidator() — tidak bisa dicek murni lewat rule tunggal
            // karena "wajib salah satu dari dua sumber" ini cross-field, dan
            // sengaja TIDAK wajib sama sekali kalau intent=draft).
            'signature_data' => ['nullable', 'string'],
            'signature_use_saved' => ['nullable', 'boolean'],
            'signature_save_as_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item pengajuan.',
            'items.*.item_name.required' => 'Nama item wajib diisi.',
            'items.*.type.required' => 'Tipe wajib diisi.',
            'items.*.unit.required' => 'Satuan wajib diisi.',
            'items.*.vendor_name.required' => 'Vendor wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
            'items.*.price_per_unit.gt' => 'Harga Satuan wajib diisi (tidak boleh 0).',
        ];
    }

    /**
     * Tanda tangan pemohon WAJIB ada sebelum benar-benar "Kirim Pengajuan"
     * (sama seperti pola "wajib foto" di modul SCM) — tapi bisa berasal
     * dari salah satu dari 2 sumber (gambar baru di kanvas, atau pakai
     * tanda tangan tersimpan di profil), jadi dicek di sini (bukan rule
     * biasa) supaya client TIDAK bisa lolos hanya dengan mem-bypass JS
     * (lihat juga validasi client-side di komponen x-signature-pad).
     * Draft (intent=draft) SENGAJA melewati cek ini sama sekali.
     */
    public function withValidator($validator): void
    {
        if ($this->input('intent') === 'draft') {
            return;
        }

        $validator->after(function ($validator) {
            $hasDrawnSignature = filled($this->input('signature_data'));
            $usesSavedSignature = $this->boolean('signature_use_saved') && $this->user()->signature_path;

            if (! $hasDrawnSignature && ! $usesSavedSignature) {
                $validator->errors()->add('signature', 'Tanda tangan pemohon wajib diisi sebelum submit pengajuan.');
            }
        });
    }
}
