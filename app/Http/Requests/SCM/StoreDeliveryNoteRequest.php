<?php

namespace App\Http\Requests\SCM;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // from_branch_id sengaja nullable di sini — utk role Gudang selalu
            // dipaksa ke branch_id miliknya sendiri di controller (bukan dari
            // input), Admin yang boleh pilih bebas lewat field ini.
            'from_branch_id' => ['nullable', 'exists:branches,id'],
            'to_branch_id' => ['required', 'exists:branches,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.batch_label_id' => ['required', 'exists:batch_labels,id'],
            'items.*.qty_sent' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],

            // Opsional: kalau diisi (mis. dari wizard mobile "Buat Surat
            // Jalan" yang menyatukan buat+kirim jadi satu alur), surat jalan
            // langsung berstatus "sent" alih-alih "draft" — lihat
            // DeliveryNoteController::store(). Tanpa foto, tetap draft dan
            // wajib lewat DeliveryNoteController::send() terpisah nanti.
            'sent_photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item label yang dikirim.',
            'items.*.batch_label_id.required' => 'Pilih label batch untuk tiap item.',
            'items.*.qty_sent.min' => 'Qty dikirim minimal 1.',
        ];
    }
}
