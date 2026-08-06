<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Dipakai oleh controller manapun yang memproses aksi "approve" berbasis
 * trait Approvable (GaRequestController-nya Head & HeadApprovalController
 * generik) supaya tanda tangan WAJIB ada sebelum approve — pakai tanda
 * tangan tersimpan di profil user (kalau dipilih & memang ada), atau
 * decode gambar baru dari kanvas (sekaligus jadi tanda tangan tersimpan
 * baru utk dipakai ulang lain kali). HANYA utk approve — reject sengaja
 * tidak butuh tanda tangan sama sekali.
 */
trait ResolvesApprovalSignature
{
    private function resolveMandatorySignature(Request $request, array $validated): string
    {
        $user = $request->user();
        $useSaved = (bool) ($validated['signature_use_saved'] ?? false);

        if ($useSaved && $user->signature_path) {
            return $user->signature_path;
        }

        if (! empty($validated['signature_data'])) {
            $path = Approval::storeSignatureImage($validated['signature_data']);
            if ($path) {
                $user->update(['signature_path' => $path]);

                return $path;
            }
        }

        throw ValidationException::withMessages([
            'signature' => 'Tanda tangan wajib diisi — gambar dulu di kanvas, atau pilih pakai tanda tangan tersimpan.',
        ]);
    }
}
