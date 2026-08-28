<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUniformRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_name' => ['required', 'string', 'max:255'],
            'issued_by_name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'branch_location_id' => [
                'nullable',
                Rule::exists('branch_locations', 'id')->where(
                    fn ($q) => $q->where('branch_id', $this->input('branch_id'))->where('is_active', true)
                ),
            ],
            'issue_date' => ['required', 'date'],
            'issue_photo' => ['required', 'image', 'max:4096'],
            'signature_data' => ['nullable', 'string'],
            'issued_by_signature_data' => ['nullable', 'string'],
            'issue_notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.uniform_stock_id' => ['required', 'exists:uniform_stocks,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.item_notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'issue_photo.required' => 'Foto serah terima wajib diunggah sebagai bukti pengambilan barang.',
            'issue_photo.image' => 'Foto serah terima harus berupa file gambar.',
            'items.required' => 'Minimal harus ada 1 item.',
            'items.min' => 'Minimal harus ada 1 item.',
            'items.*.uniform_stock_id.required' => 'Varian item wajib dipilih.',
            'items.*.qty.required' => 'Qty item wajib diisi.',
            'items.*.qty.min' => 'Qty item minimal 1.',
        ];
    }
}
