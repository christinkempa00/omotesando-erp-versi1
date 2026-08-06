<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PO kategori 'food' — dibuat Purchasing dari PurchaseRequisition yang
 * sudah disetujui. category selalu 'food' (lihat
 * PurchaseOrderController::storeFromRequisition). branch_id TETAP dipilih
 * bebas di sini (bukan auto-ikut branch outlet pemohon PR) — barang dari
 * supplier biasanya dikirim ke Central Kitchen/Storage dulu, baru
 * didistribusikan ke outlet lewat Surat Jalan (modul SCM), bukan langsung
 * ke outlet pemohon.
 */
class StorePurchaseOrderFromRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
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
            'items.*.unit_price.required' => 'Harga/unit wajib diisi.',
        ];
    }
}
