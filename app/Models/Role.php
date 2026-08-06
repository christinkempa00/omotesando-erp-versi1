<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Nama role baku yang dipakai di seluruh sistem.
     * Dipakai sebagai referensi supaya tidak ada "magic string" tersebar.
     */
    public const GA = 'GA';
    public const FINANCE = 'Finance';
    public const HR = 'HR';
    public const COST_CONTROL = 'Cost Control';
    public const HEAD = 'Head';
    public const ADMIN = 'Admin';
    public const IT = 'IT';
    public const PRODUKSI = 'Produksi';
    public const GUDANG = 'Gudang';
    public const OUTLET = 'Outlet';
    public const PURCHASING = 'Purchasing';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
