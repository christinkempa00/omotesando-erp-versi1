<?php

namespace App\Http\Requests\GA;

use App\Models\GA\GaRequest;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'category' => ['required', 'in:'.implode(',', array_keys(GaRequest::categoryLabels()))],
            'description' => ['required', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price_per_unit' => ['required', 'numeric', 'min:0'],
            'items.*.vendor_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item pengajuan.',
            'items.*.item_name.required' => 'Nama item wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
        ];
    }
}
