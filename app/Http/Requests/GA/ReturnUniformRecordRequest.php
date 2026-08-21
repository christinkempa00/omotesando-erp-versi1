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
            'returned_by_name' => ['required', 'string', 'max:255'],
            'received_by_name' => ['required', 'string', 'max:255'],

            // 3 pertanyaan Ya/Tidak (dok. "Pemeriksaan Pengembalian Barang") —
            // wajib dijawab tegas tiap submit baru, walau kolomnya nullable di
            // DB (record lama sebelum fitur ini tidak akan pernah mengisinya).
            'qty_sesuai' => ['required', 'boolean'],
            'qty_sesuai_notes' => ['nullable', 'string', 'max:255'],
            'spesifikasi_sesuai' => ['required', 'boolean'],
            'spesifikasi_sesuai_notes' => ['nullable', 'string', 'max:255'],
            'kondisi_sesuai' => ['required', 'boolean'],
            'kondisi_sesuai_notes' => ['nullable', 'string', 'max:255'],

            'return_signature_data' => ['nullable', 'string'],
            'received_by_signature_data' => ['nullable', 'string'],

            // Tetap ada, tetap yang menentukan pergerakan stok — terpisah dari
            // 3 pertanyaan Ya/Tidak di atas yang murni dokumentasi pemeriksaan.
            'return_condition' => ['required', 'in:'.implode(',', array_keys(UniformRecord::conditionLabels()))],
            'return_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
