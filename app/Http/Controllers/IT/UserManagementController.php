<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Http\Requests\IT\StoreUserRequest;
use App\Http\Requests\IT\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Division;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Manajemen User — khusus role IT. Satu-satunya jalur pembuatan akun baru
 * sekarang (/register publik sudah dihapus, lihat routes/auth.php). Beda
 * dari ModuleControlController (aktif/nonaktifkan modul & mode maintenance,
 * berlaku utk SEMUA user role tsb): controller ini menentukan APA saja
 * modul yang boleh diakses PER USER lewat module_user (lihat User::modules()),
 * dgn module_role cuma dipakai sbg saran default awal saat bikin akun baru.
 */
class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles', 'branch', 'division'])->orderBy('name')->get();

        return view('it.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('it.users.create', $this->formData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'division_id' => $validated['division_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => true,
            // Akun baru selalu wajib ganti password sendiri di login pertama —
            // lihat EnsurePasswordChanged & alur di AuthenticatedSessionController.
            'password_must_change' => true,
        ]);

        $user->roles()->sync($validated['roles']);
        $user->modules()->sync($validated['modules'] ?? []);

        ActivityLog::record($request->user(), 'user.created', $user, "Membuat akun baru {$user->name} ({$user->email})", [
            'roles' => Role::whereIn('id', $validated['roles'])->pluck('name')->all(),
            'modules' => Module::whereIn('id', $validated['modules'] ?? [])->pluck('key')->all(),
        ]);

        return redirect()->route('it.users.index')
            ->with('success', "Akun {$user->name} berhasil dibuat. Sampaikan password awal ke user secara manual — password tidak dikirim otomatis.");
    }

    public function edit(User $user): View
    {
        $user->load(['roles', 'modules']);

        return view('it.users.edit', $this->formData() + ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'division_id' => $validated['division_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->roles()->sync($validated['roles']);
        $user->modules()->sync($validated['modules'] ?? []);

        ActivityLog::record($request->user(), 'user.updated', $user, "Memperbarui akun {$user->name}", [
            'roles' => Role::whereIn('id', $validated['roles'])->pluck('name')->all(),
            'modules' => Module::whereIn('id', $validated['modules'] ?? [])->pluck('key')->all(),
            'is_active' => $user->is_active,
        ]);

        return redirect()->route('it.users.edit', $user)->with('success', "Perubahan akun {$user->name} disimpan.");
    }

    /**
     * Hapus permanen — hanya berhasil kalau user belum pernah bikin record
     * apa pun di modul lain (GA request/asset/maintenance/dll — kolom
     * created_by/requested_by di tabel-tabel itu FK ke users TANPA
     * nullOnDelete, jadi DB menolak hapusnya lewat QueryException). Untuk
     * user yang sudah punya riwayat data, arahkan ke nonaktifkan (is_active)
     * lewat form edit, bukan hapus permanen.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('it.users.index')
                ->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        $name = $user->name;

        try {
            $user->delete();
        } catch (QueryException) {
            return redirect()->route('it.users.index')
                ->with('error', "Akun {$name} tidak bisa dihapus karena masih punya riwayat data (mis. pernah membuat pengajuan/aset/dll). Nonaktifkan saja lewat form edit.");
        }

        ActivityLog::record($request->user(), 'user.deleted', $user, "Menghapus akun {$name}");

        return redirect()->route('it.users.index')->with('success', "Akun {$name} berhasil dihapus.");
    }

    /**
     * @return array{roles: \Illuminate\Support\Collection, divisions: \Illuminate\Support\Collection, branches: \Illuminate\Support\Collection, modules: \Illuminate\Support\Collection, roleModuleDefaults: \Illuminate\Support\Collection}
     */
    private function formData(): array
    {
        // Default modul per role (dari module_role, dibuat Head lewat seeder)
        // — dikirim ke view sbg peta role_id => [module_id, ...] supaya JS
        // form bisa auto-centang saran modul saat IT memilih role, TANPA
        // reload halaman. Ini murni saran awal; yg disimpan ke module_user
        // adalah checklist final saat submit (lihat store()/update() di atas).
        $roleModuleDefaults = DB::table('module_role')
            ->select('role_id', 'module_id')
            ->get()
            ->groupBy('role_id')
            ->map(fn ($rows) => $rows->pluck('module_id')->values());

        return [
            'roles' => Role::orderBy('name')->get(),
            'divisions' => Division::orderBy('name')->get(),
            'branches' => Branch::orderedOutlets(),
            'modules' => Module::orderBy('label')->get(),
            'roleModuleDefaults' => $roleModuleDefaults,
        ];
    }
}
