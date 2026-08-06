<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_active — dicek saat login (lihat LoginRequest::authenticate()),
     * dikontrol IT lewat Manajemen User.
     * password_must_change — wajib true utk akun baru/reset password IT,
     * dibaca middleware EnsurePasswordChanged; jadi false lagi otomatis
     * setelah user berhasil ganti password (lihat PasswordController::update()).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('password_must_change')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'password_must_change']);
        });
    }
};
