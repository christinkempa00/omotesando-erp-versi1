<?php

namespace App\Http\Requests\SCM;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Konfirmasi kirim surat jalan — foto WAJIB (beda dari lampiran GA yang
 * nullable), sesuai permintaan "wajib upload foto sebelum kirim".
 */
class SendDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sent_photo' => ['required', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'sent_photo.required' => 'Foto bukti sebelum kirim wajib diunggah.',
        ];
    }
}
