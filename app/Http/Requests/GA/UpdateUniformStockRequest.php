<?php

namespace App\Http\Requests\GA;

use App\Models\GA\UniformStock;
use Illuminate\Foundation\Http\FormRequest;

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
            'uniform_type' => ['required', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(UniformStock::statusLabels()))],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'stock_photo' => ['nullable', 'image', 'max:4096'], // max 4MB
        ];
    }
}
