<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE users SET role = 'bagian_administrasi' WHERE role = 'admin'");
        DB::statement("UPDATE users SET role = 'bagian_keuangan' WHERE role = 'manajemen'");

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('bagian_administrasi','bagian_keuangan','pimpinan') NOT NULL DEFAULT 'bagian_administrasi'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin'");
        }

        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'bagian_administrasi'");
        DB::statement("UPDATE users SET role = 'manajemen' WHERE role = 'bagian_keuangan'");
        DB::statement("UPDATE users SET role = 'manajemen' WHERE role = 'pimpinan'");
    }
};
