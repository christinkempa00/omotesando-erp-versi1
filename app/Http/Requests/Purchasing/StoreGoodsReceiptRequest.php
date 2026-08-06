<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Konfirmasi barang diterima dari supplier — foto WAJIB, sama seperti pola
 * terima kiriman internal (lihat SCM\StoreDeliveryReceiptRequest).
 */
class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
            // Nullable — cuma relevan utk item kategori 'food'.
            'items.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Foto bukti barang diterima wajib diunggah.',
            'items.required' => 'Konfirmasi qty diterima utk tiap item.',
        ];
    }
}
