<?php

namespace App\Http\Requests\GA;

use App\Models\GA\UniformRecord;
use Illuminate\Foundation\Http\FormRequest;

class ReturnUniformRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'return_condition' => ['required', 'in:'.implode(',', array_keys(UniformRecord::conditionLabels()))],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
