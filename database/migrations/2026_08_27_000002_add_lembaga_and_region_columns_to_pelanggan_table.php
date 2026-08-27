<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->string('kode_pelanggan', 50)->nullable()->unique()->after('no_telepon');
            $table->string('kode_lembaga', 50)->nullable()->after('kode_pelanggan');
            $table->string('nama_lembaga', 200)->nullable()->after('kode_lembaga');
            $table->string('status_lembaga', 20)->nullable()->after('nama_lembaga');
            $table->string('provinsi', 100)->nullable()->after('status_lembaga');
            $table->string('kabupaten', 100)->nullable()->after('provinsi');
            $table->string('kecamatan', 100)->nullable()->after('kabupaten');
            $table->string('desa', 100)->nullable()->after('kecamatan');
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn([
                'kode_pelanggan',
                'kode_lembaga',
                'nama_lembaga',
                'status_lembaga',
                'provinsi',
                'kabupaten',
                'kecamatan',
                'desa',
            ]);
        });
    }
};
