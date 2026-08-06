<?php

namespace App\Http\Requests\SCM;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware route sudah membatasi role (role:Produksi,...), jadi di
        // sini cukup true untuk user yang sudah lolos middleware itu.
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'description' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item bahan.',
            'items.*.item_name.required' => 'Nama bahan wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
        ];
    }
}
