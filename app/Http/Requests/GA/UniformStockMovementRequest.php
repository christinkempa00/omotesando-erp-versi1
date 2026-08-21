<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dipakai oleh aksi Restock (satu-satunya movement manual yang tersisa di
 * Stock Management — Issue/Adjustment/Disposal sudah dihapus, penambahan
 * stok rusak tercatat lewat form pengembalian di Kartu Seragam Karyawan).
 */
class UniformStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
