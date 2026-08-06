<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;

class StoreUniformRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uniform_stock_id' => ['required', 'exists:uniform_stocks,id'],
            'employee_name' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'issue_photo' => ['required', 'image', 'max:4096'],
            'signature_data' => ['nullable', 'string'],
            'issue_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'issue_photo.required' => 'Foto serah terima wajib diunggah sebagai bukti pengambilan barang.',
            'issue_photo.image' => 'Foto serah terima harus berupa file gambar.',
        ];
    }
}
