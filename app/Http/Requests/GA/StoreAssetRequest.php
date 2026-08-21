<?php

namespace App\Http\Requests\GA;

use App\Models\GA\Asset;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Aset yang sedang diedit (null kalau ini request store/create).
        // Foto cuma wajib diupload ulang kalau asetnya belum punya foto sama
        // sekali — supaya edit aset lama yang sudah punya foto tidak dipaksa
        // upload ulang tiap kali disimpan (input file tidak bisa "pre-filled").
        $asset = $this->route('asset');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],

            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expiry' => ['nullable', 'date'],
            'vendor_contact' => ['nullable', 'string', 'max:255'],

            'status' => ['required', 'in:'.implode(',', array_keys(Asset::statusLabels()))],
            'location' => ['nullable', 'in:Kitchen,Floor,Bar,Lainnya'],
            'branch_id' => ['required', 'exists:branches,id'],
            'custodian_name' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string', 'max:2000'],
            'image' => [$asset?->image_path ? 'nullable' : 'required', 'image', 'max:4096'], // max 4MB
            'serial_number_photo' => [$asset?->serial_number_photo_path ? 'nullable' : 'required', 'image', 'max:4096'],

            'sp3_number' => ['nullable', 'string', 'max:255'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'receive_date' => ['nullable', 'date'],

            'dimension_p' => ['nullable', 'numeric', 'min:0'],
            'dimension_l' => ['nullable', 'numeric', 'min:0'],
            'dimension_t' => ['nullable', 'numeric', 'min:0'],

            'quantity' => ['required', 'integer', 'min:1'],
            'depreciation_value' => ['nullable', 'numeric', 'min:0'],

            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
