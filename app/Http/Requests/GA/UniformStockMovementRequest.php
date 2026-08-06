<?php

namespace App\Http\Requests\GA;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dipakai bersama oleh 4 aksi cepat stok (restock, issue, adjustment, disposal).
 * Aturan quantity berbeda per aksi: restock/issue/disposal harus positif
 * (arah naik/turun ditentukan controller by movement_type), adjustment boleh
 * negatif karena itu koreksi langsung ke angka available_stock.
 */
class UniformStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isAdjustment = $this->routeIs('ga.uniforms.stocks.adjustment');

        return [
            'quantity' => $isAdjustment
                ? ['required', 'integer', 'not_in:0']
                : ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $stock = $this->route('stock');
            $quantity = (int) $this->input('quantity');

            if ($this->routeIs('ga.uniforms.stocks.issue') && $quantity > $stock->available_stock) {
                $validator->errors()->add('quantity', "Stok tersedia cuma {$stock->available_stock}.");
            }

            if ($this->routeIs('ga.uniforms.stocks.disposal') && $quantity > $stock->unusable_stock) {
                $validator->errors()->add('quantity', "Stok rusak cuma {$stock->unusable_stock}.");
            }

            if ($this->routeIs('ga.uniforms.stocks.adjustment') && $stock->available_stock + $quantity < 0) {
                $validator->errors()->add('quantity', 'Hasil penyesuaian tidak boleh membuat stok tersedia negatif.');
            }
        });
    }
}
