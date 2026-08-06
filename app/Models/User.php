<?php

namespace App\Models;

// Illuminate\Foundation\Auth\User as Authenticatable
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
}
