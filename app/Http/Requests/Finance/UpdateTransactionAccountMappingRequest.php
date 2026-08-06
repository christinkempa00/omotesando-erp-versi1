<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionAccountMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debit_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'credit_account_id' => ['required', 'different:debit_account_id', 'exists:chart_of_accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_account_id.different' => 'Akun debit dan kredit tidak boleh sama.',
        ];
    }
}
