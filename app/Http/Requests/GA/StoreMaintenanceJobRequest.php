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
        // Jadwal tidak boleh dibuat utk hari yang sudah lewat — tapi cek ini
        // cuma berlaku saat MEMBUAT jadwal baru (bukan route('maintenance')),
        // supaya jadwal lama yang sudah terlanjur lewat tanggal (overdue)
        // tetap bisa disimpan ulang/diedit tanpa terblokir aturan ini.
        $isCreate = ! $this->route('maintenance');

        $dateRules = ['required', 'date'];
        if ($isCreate) {
            $dateRules[] = 'after_or_equal:today';
        }

        return [
            'asset_id' => ['required', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],

            'type' => ['required', 'in:'.implode(',', array_keys(MaintenanceJob::typeLabels()))],
            'priority' => ['required', 'in:'.implode(',', array_keys(MaintenanceJob::priorityLabels()))],
            'status' => ['required', 'in:'.implode(',', array_keys(MaintenanceJob::statusLabels()))],

            'scheduled_date' => $dateRules,
            'scheduled_time' => ['required', 'date_format:H:i'],
            'scheduled_end_time' => ['nullable', 'date_format:H:i'],

            'pic_name' => ['required', 'string', 'max:255'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],

            'cost' => ['nullable', 'numeric', 'min:0'],

            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['nullable', 'string', 'max:255'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('scheduled_time');
            $end = $this->input('scheduled_end_time');

            if ($start && $end && $end <= $start) {
                $validator->errors()->add('scheduled_end_time', 'Waktu selesai harus setelah jam mulai.');
            }

            // Sama seperti aturan tanggal — cuma berlaku saat membuat jadwal
            // baru, supaya jadwal lama yang sudah terlanjur lewat tetap bisa
            // disimpan ulang tanpa terblokir.
            $isCreate = ! $this->route('maintenance');
            $date = $this->input('scheduled_date');

            if ($isCreate && $date === now()->toDateString() && $start && $start <= now()->format('H:i')) {
                $validator->errors()->add('scheduled_time', 'Jam mulai harus setelah jam saat ini untuk jadwal hari ini.');
            }
        });
    }
}
