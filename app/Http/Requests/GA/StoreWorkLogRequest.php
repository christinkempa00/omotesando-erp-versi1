<?php

namespace App\Http\Requests\GA;

use App\Models\Branch;
use App\Models\GA\WorkLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'category' => ['required', 'in:'.implode(',', array_keys(WorkLog::categoryLabels()))],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->whereIn('name', Branch::WORK_LOG_OUTLETS)),
            ],

            'technician_in_charge' => ['required', Rule::in(WorkLog::technicianOptions())],
            'technician_assist' => ['nullable', 'string', 'max:255'],

            'work_detail' => ['required', 'string', 'max:5000'],
            // Nullable — boleh diisi belakangan begitu pekerjaan selesai
            // (lihat WorkLog::isComplete()), bukan wajib saat log dibuat.
            'work_result' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['image', 'max:4096'], // max 4MB per foto
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'Waktu berakhir harus setelah waktu mulai.',
            'technician_in_charge.in' => 'Pilih teknisi in charge dari daftar yang tersedia.',
        ];
    }
}
