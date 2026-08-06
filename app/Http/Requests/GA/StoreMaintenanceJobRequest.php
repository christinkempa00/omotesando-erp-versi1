<?php

namespace App\Http\Requests\GA;

use App\Models\GA\MaintenanceJob;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],

            'type' => ['required', 'in:'.implode(',', array_keys(MaintenanceJob::typeLabels()))],
            'priority' => ['required', 'in:'.implode(',', array_keys(MaintenanceJob::priorityLabels()))],
            'status' => ['required', 'in:'.implode(',', array_keys(MaintenanceJob::statusLabels()))],

            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],

            'pic_name' => ['nullable', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'exists:branches,id'],

            'cost' => ['nullable', 'numeric', 'min:0'],

            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'string', 'max:255'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
