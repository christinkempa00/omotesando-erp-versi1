<?php

namespace App\Http\Requests\Outlet;

use App\Models\GA\OutletInspectionArea;
use App\Models\GA\OutletReport;
use App\Models\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * Validasi form laporan kebersihan outlet (opening/closing) — foto
 * dikelompokkan per area pemeriksaan (photos[<area_id>][], sepasang dgn
 * latitude[<area_id>][]/longitude[<area_id>][] dari GPS device saat foto
 * dipilih — lihat form JS di create.blade.php, Batch 3).
 */
class StoreOutletReportRequest extends FormRequest
{
    private ?Collection $activeAreasCache = null;

    public function authorize(): bool
    {
        if (! ($this->user()?->hasRole(Role::OUTLET) ?? false)) {
            return false;
        }

        $branch = $this->user()->scopingBranch();
        if (! $branch) {
            return false;
        }

        // Setiap area_id yang dikirim (key array photos/latitude/longitude)
        // harus benar-benar milik branch akun ini — cegah user coba kirim
        // foto atas nama area outlet lain lewat request dipalsukan.
        $submittedAreaIds = array_keys((array) $this->input('photos', []));
        if (empty($submittedAreaIds)) {
            return true; // rules() yang akan menolak "photos wajib diisi".
        }

        $ownedAreaIds = OutletInspectionArea::where('branch_id', $branch->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        foreach ($submittedAreaIds as $areaId) {
            if (! in_array((string) $areaId, $ownedAreaIds, true)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'session' => ['required', 'in:'.OutletReport::SESSION_OPENING.','.OutletReport::SESSION_CLOSING],
            'report_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'photos' => ['required', 'array'],
            'photos.*' => ['array'],
            'photos.*.*' => ['file', 'image', 'mimes:jpeg,jpg,png', 'max:8192'],

            'latitude' => ['array'],
            'latitude.*' => ['array'],
            'latitude.*.*' => ['required', 'numeric', 'between:-90,90'],

            'longitude' => ['array'],
            'longitude.*' => ['array'],
            'longitude.*.*' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'session.required' => 'Sesi (Opening/Closing) wajib dipilih.',
            'session.in' => 'Sesi tidak valid.',
            'report_date.required' => 'Tanggal laporan wajib diisi.',
            'report_date.before_or_equal' => 'Tanggal laporan tidak boleh di masa depan.',
            'photos.required' => 'Minimal harus ada 1 foto.',
            'photos.*.*.image' => 'File yang diunggah harus berupa gambar.',
            'photos.*.*.mimes' => 'Foto harus berformat JPG atau PNG.',
            'photos.*.*.max' => 'Ukuran foto maksimal 8 MB.',
            'latitude.*.*.required' => 'Data lokasi (GPS) wajib ada untuk setiap foto.',
            'longitude.*.*.required' => 'Data lokasi (GPS) wajib ada untuk setiap foto.',
        ];
    }

    /**
     * Area aktif milik branch akun ini, terurut sort_order — dipakai ulang
     * di withValidator() (cek kelengkapan per area) supaya query area tidak
     * dijalankan dua kali.
     */
    public function activeAreas(): Collection
    {
        if ($this->activeAreasCache === null) {
            $branch = $this->user()?->scopingBranch();

            $this->activeAreasCache = $branch
                ? OutletInspectionArea::where('branch_id', $branch->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                : collect();
        }

        return $this->activeAreasCache;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->user()?->scopingBranch()) {
                return;
            }

            // File upload tidak muncul di input(), harus lewat file().
            $photosByArea = $this->file('photos') ?? [];
            $latByArea = (array) $this->input('latitude', []);
            $lngByArea = (array) $this->input('longitude', []);

            foreach ($this->activeAreas() as $area) {
                $photos = $photosByArea[$area->id] ?? [];
                $photoCount = count($photos);

                if ($photoCount === 0) {
                    $validator->errors()->add(
                        "photos.{$area->id}",
                        "Area \"{$area->name}\" wajib minimal 1 foto."
                    );

                    continue;
                }

                if ($photoCount > 10) {
                    $validator->errors()->add(
                        "photos.{$area->id}",
                        "Area \"{$area->name}\" maksimal 10 foto."
                    );
                }

                $latCount = count($latByArea[$area->id] ?? []);
                $lngCount = count($lngByArea[$area->id] ?? []);

                if ($latCount !== $photoCount || $lngCount !== $photoCount) {
                    $validator->errors()->add(
                        "photos.{$area->id}",
                        "Data lokasi (GPS) untuk area \"{$area->name}\" tidak lengkap — coba ambil ulang foto."
                    );
                }
            }
        });
    }
}
