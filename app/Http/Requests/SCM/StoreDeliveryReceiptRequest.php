<?php

namespace App\Http\Requests\SCM;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Konfirmasi terima di outlet — foto WAJIB, sama seperti saat kirim
 * (lihat SendDeliveryNoteRequest).
 */
class StoreDeliveryReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_photo' => ['required', 'image', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.delivery_note_item_id' => ['required', 'exists:delivery_note_items,id'],
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'received_photo.required' => 'Foto bukti saat terima wajib diunggah.',
            'items.required' => 'Konfirmasi qty diterima utk tiap item.',
        ];
    }
}
