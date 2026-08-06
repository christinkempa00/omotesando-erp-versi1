<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PO kategori 'general' — dibuat langsung oleh GA, tujuan (branch_id)
 * dipilih bebas di form ini. Beda dengan StorePurchaseOrderFromRequisitionRequest
 * (kategori 'food') yang branch_id-nya ikut PurchaseRequisition, bukan input.
 */
class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role sudah dicek di controller, sesuai konvensi form request lain.
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'order_date' => ['required', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item barang.',
            'items.*.item_name.required' => 'Nama barang wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
        ];
    }
}
