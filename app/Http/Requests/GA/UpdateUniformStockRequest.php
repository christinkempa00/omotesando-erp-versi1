<?php

namespace App\Http\Requests\GA;

use App\Models\GA\UniformStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai khusus untuk edit satu varian yang sudah ada (metadata saja).
 * Untuk membuat varian baru multi-ukuran sekaligus, lihat StoreUniformStockRequest.
 */
class UpdateUniformStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'branch_location_id' => [
                'nullable',
                Rule::exists('branch_locations', 'id')->where(
                    fn ($q) => $q->where('branch_id', $this->input('branch_id'))->where('is_active', true)
                ),
            ],
            'uniform_type' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(UniformStock::statusLabels()))],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'stock_photo' => ['nullable', 'image', 'max:4096'], // max 4MB
        ];
    }

    public function messages(): array
    {
        return [
            'color.required' => 'Warna wajib diisi.',
        ];
    }
}
