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
    public const HEAD = 'Head';
    public const IT = 'IT';
    public const OUTLET = 'Outlet';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
