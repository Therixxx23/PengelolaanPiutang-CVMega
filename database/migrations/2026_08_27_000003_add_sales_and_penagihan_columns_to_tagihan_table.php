<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('kode_sales')->nullable()->after('id_pelanggan');
            $table->string('nama_sales')->nullable()->after('kode_sales');
            $table->string('sumber_dana')->nullable()->after('total_tagihan');
            $table->enum('status_penagihan', [
                'belum_ditagih',
                'sedang_ditagih',
                'janji_bayar',
                'sudah_ditagih',
            ])->default('belum_ditagih')->after('sumber_dana');
            $table->text('catatan_penagihan_terakhir')->nullable()->after('status_penagihan');

            $table->foreignId('assigned_sales_id')
                ->nullable()
                ->after('id_pelanggan')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('no_sj', 50)->nullable()->after('no_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropForeign(['assigned_sales_id']);
            $table->dropColumn([
                'kode_sales',
                'nama_sales',
                'sumber_dana',
                'status_penagihan',
                'catatan_penagihan_terakhir',
                'assigned_sales_id',
                'no_sj',
            ]);
        });
    }
};
