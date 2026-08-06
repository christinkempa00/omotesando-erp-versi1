<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Catat pembayaran baru ke invoice ini — nilainya DITAMBAHKAN ke paid_amount
 * yang sudah ada (bukan menggantikan), supaya mendukung pembayaran bertahap
 * (status 'partial').
 */
class UpdateSupplierInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_paid_now' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
