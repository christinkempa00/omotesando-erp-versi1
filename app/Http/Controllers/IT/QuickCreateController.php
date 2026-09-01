<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Division;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Endpoint kecil dipanggil via fetch dari form Manajemen User (lihat
 * it/users/_contact-fields.blade.php) supaya IT bisa tambah opsi Divisi/
 * Branch baru langsung dari dropdown, tanpa perlu akses backend/DB manual.
 * `code` di kedua tabel wajib & unik (lihat migration) tapi tidak relevan
 * dipilih manual di sini — di-generate otomatis dari nama.
 */
class QuickCreateController extends Controller
{
    public function division(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:divisions,name'],
        ]);

        $division = Division::create([
            'name' => $validated['name'],
            'code' => $this->uniqueCode(Division::class, $validated['name']),
        ]);

        return response()->json(['id' => $division->id, 'name' => $division->name, 'code' => $division->code], 201);
    }

    public function branch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:branches,name'],
        ]);

        $branch = Branch::create([
            'name' => $validated['name'],
            'code' => $this->uniqueCode(Branch::class, $validated['name']),
            'is_active' => true,
        ]);

        return response()->json(['id' => $branch->id, 'name' => $branch->name], 201);
    }

    /**
     * @param  class-string<Division|Branch>  $modelClass
     */
    private function uniqueCode(string $modelClass, string $name): string
    {
        $base = Str::of($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '')->substr(0, 10)->toString();
        $base = $base !== '' ? $base : 'X';

        $code = $base;
        $suffix = 1;
        while ($modelClass::where('code', $code)->exists()) {
            $code = $base.$suffix++;
        }

        return $code;
    }
}
