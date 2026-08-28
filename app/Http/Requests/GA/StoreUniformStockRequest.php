<?php

namespace App\Http\Requests\GA;

use App\Models\GA\UniformStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'branch_location_id' => [
                'nullable',
                Rule::exists('branch_locations', 'id')->where(
                    fn ($q) => $q->where('branch_id', $this->input('branch_id'))->where('is_active', true)
                ),
            ],
            'uniform_type' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:100'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'stock_photo' => ['nullable', 'image', 'max:4096'], // max 4MB

            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.name' => ['nullable', 'string', 'max:50'],
            'sizes.*.qty' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'color.required' => 'Warna wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasStock = collect($this->input('sizes', []))
                ->contains(fn ($row) => trim((string) ($row['name'] ?? '')) !== '' && (int) ($row['qty'] ?? 0) > 0);

            if ($hasStock) {
                return;
            }

            // Boleh kosongkan semua baris ukuran KALAU varian (kombinasi
            // tipe+outlet+warna) ini sudah ada — berarti user cuma mau
            // mengedit metadata (Ambang Low Stock / Foto Varian) lewat form
            // ini, bukan menambah stok baru. Grup yang benar-benar baru
            // tetap wajib diisi minimal satu ukuran, tidak ada gunanya
            // membuat grup kosong tanpa stok sama sekali.
            $groupExists = UniformStock::where('branch_id', $this->input('branch_id'))
                ->where('uniform_type', $this->input('uniform_type'))
                ->where('color', $this->input('color') ?: null)
                ->exists();

            if (! $groupExists) {
                $validator->errors()->add('sizes', 'Isi ukuran & jumlah stok untuk minimal satu baris.');
            }
        });
    }
}
