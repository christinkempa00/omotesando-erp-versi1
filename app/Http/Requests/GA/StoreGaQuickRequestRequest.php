<?php

namespace App\Http\Requests\GA;

use App\Models\GA\GaQuickRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGaQuickRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'branch_location_id' => [
                'nullable',
                Rule::exists('branch_locations', 'id')->where(
                    fn ($q) => $q->where('branch_id', $this->input('branch_id'))->where('is_active', true)
                ),
            ],
            'user_name' => ['required', 'string', 'max:255'],
            'pic_name' => ['required', 'string', 'max:255'],
            'urgency' => ['required', 'in:'.implode(',', array_keys(GaQuickRequest::urgencyLabels()))],
            'needs_description' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.photo_link' => ['nullable', 'string', 'max:500'],
        ];
    }
}
