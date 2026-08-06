<?php

namespace App\Http\Requests\GA;

use App\Models\GA\UniformStock;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Buat varian seragam baru — user isi jumlah stok utk BEBERAPA ukuran
 * sekaligus dalam satu form (bukan satu-satu per ukuran).
 */
class StoreUniformStockRequest extends FormRequest
{
    public const SIZES = ['S', 'M', 'L', 'XL', 'XXL'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'uniform_type' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:'.implode(',', array_keys(UniformStock::statusLabels()))],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'stock_photo' => ['nullable', 'image', 'max:4096'], // max 4MB

            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.name' => ['nullable', 'string', 'max:50'],
            'sizes.*.qty' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasStock = collect($this->input('sizes', []))
                ->contains(fn ($row) => trim((string) ($row['name'] ?? '')) !== '' && (int) ($row['qty'] ?? 0) > 0);

            if (! $hasStock) {
                $validator->errors()->add('sizes', 'Isi ukuran & jumlah stok untuk minimal satu baris.');
            }
        });
    }
}
