<?php

namespace App\Models;

// Illuminate\Foundation\Auth\User as Authenticatable
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'division_id',
        'branch_id',
        'is_active',
        'password_must_change',
        'signature_path',
        'telegram_chat_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'password_must_change' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Outlet/gudang tempat user ini bertugas — dipakai modul SCM untuk
     * membatasi akses DeliveryNote (Outlet cuma boleh lihat/proses yang
     * to_branch_id-nya sama, Gudang terikat ke from_branch_id-nya sendiri).
     * Nullable karena tidak semua role butuh ini (mis. GA/Finance/Head).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Branch yang MEMBATASI data operasional user ini — cuma berlaku utk
     * role Outlet. Staff role lain (GA/Head/dll) bisa saja py branch_id
     * terisi (mis. penempatan kantor administratif), tapi itu BUKAN berarti
     * data yang mereka lihat harus dibatasi ke branch itu — insiden nyata:
     * staff GA dgn branch_id="Head Office" jadi cuma lihat data Head Office
     * (nyaris kosong) begitu branch auto-scope diperkenalkan sblm guard
     * role ini ada. Semua query branch-scoping HARUS lewat method ini,
     * BUKAN langsung ->branch, supaya kasus ini tidak terulang.
     */
    public function scopingBranch(): ?Branch
    {
        return $this->hasRole(Role::OUTLET) ? $this->branch : null;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Cek apakah user punya salah satu dari role yang diberikan.
     * Dipakai oleh RoleMiddleware, juga bisa dipanggil manual di controller/blade.
     */
    public function hasRole(string ...$roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Daftar modul yang boleh diakses user INI secara eksplisit — sumber
     * kebenaran akses modul (dicek ModuleAccessMiddleware), BUKAN lagi
     * murni ikut role. Role dipakai IT cuma sbg saran default saat bikin
     * akun baru (lihat UserManagementController), sesudah itu daftar ini
     * independen & bisa diubah IT kapan saja tanpa mengubah role user.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_user');
    }

    public function pagePermissions(): HasMany
    {
        return $this->hasMany(UserPagePermission::class);
    }

    /**
     * Tier akses (view/edit) user ini ke satu halaman GA (lihat konstanta
     * UserPagePermission::PAGE_*). Tidak ada baris utk halaman itu = default
     * 'edit' (behavior lama, sebelum tier ini ada).
     */
    public function canEdit(string $pageKey): bool
    {
        $level = $this->pagePermissions()->where('page_key', $pageKey)->value('access_level');

        return ($level ?? UserPagePermission::ACCESS_EDIT) === UserPagePermission::ACCESS_EDIT;
    }

    /**
     * Teks identitas akun ini, ditampilkan di kiri-atas sidebar & topbar
     * mobile (lihat layouts/app.blade.php & partials/sidebar.blade.php tiap
     * portal). Divisi (diatur IT lewat Manajemen User) jadi sumber utama;
     * kalau kosong, jatuh ke nama branch utk role Outlet (perilaku lama yang
     * dipertahankan), lalu ke nama role sbg fallback terakhir.
     */
    public function identityLabel(): string
    {
        if ($this->division) {
            return $this->division->name;
        }

        if ($this->hasRole(Role::OUTLET) && $this->branch) {
            return $this->branch->name;
        }

        return $this->roles->pluck('name')->join(' & ') ?: 'User';
    }
}
