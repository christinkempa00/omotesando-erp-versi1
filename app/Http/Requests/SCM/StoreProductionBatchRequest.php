<?php

namespace App\Http\Requests\SCM;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_request_id' => ['required', 'exists:material_requests,id'],

            // Batch bisa menghasilkan beberapa produk berbeda dari bahan yang sama.
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            // Nullable — Produksi tidak selalu tahu biaya persis saat input
            // batch. Dipakai Laporan Nilai Persediaan (Fase 1).
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 produk hasil batch.',
            'items.*.item_name.required' => 'Nama produk wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
        ];
    }
}
