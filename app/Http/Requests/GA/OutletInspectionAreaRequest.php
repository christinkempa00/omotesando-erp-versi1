<?php

namespace App\Http\Requests\GA;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai bareng utk store() & update() (pola sama StoreWorkLogRequest).
 * branch_id divalidasi tetap ada meski update() tidak pernah menerapkannya
 * ke model (lihat OutletInspectionAreaController::update()) — defense in
 * depth, supaya field hidden branch_id tidak bisa dipakai memindah area
 * ke outlet lain lewat request yang dimanipulasi.
 *
 * sort_order SENGAJA tidak ada di sini — bukan lagi input manual di form
 * tambah/edit, otomatis (append di akhir saat dibuat) & diatur lewat drag
 * di tabel (lihat OutletInspectionAreaController::reorder(), rute PATCH
 * terpisah dgn validasi sendiri, bukan lewat FormRequest ini).
 */
class OutletInspectionAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->whereIn('name', Branch::MONITORING_OUTLETS)),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
