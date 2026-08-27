<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->string('kode_pelanggan')->nullable()->after('id_pelanggan');
            $table->string('kode_lembaga')->nullable()->after('nama_pelanggan');
            $table->string('nama_lembaga')->nullable()->after('kode_lembaga');
            $table->string('status_lembaga')->nullable()->after('nama_lembaga');
            $table->string('provinsi')->nullable()->after('alamat');
            $table->string('kabupaten')->nullable()->after('provinsi');
            $table->string('kecamatan')->nullable()->after('kabupaten');
            $table->string('desa')->nullable()->after('kecamatan');
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
