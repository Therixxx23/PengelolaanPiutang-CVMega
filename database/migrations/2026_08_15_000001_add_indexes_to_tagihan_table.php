<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->index(['status', 'approval_status'], 'idx_tagihan_status_approval');
            $table->index('tanggal_jatuh_tempo', 'idx_tagihan_jatuh_tempo');
            $table->index('tanggal_tagihan', 'idx_tagihan_tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropIndex('idx_tagihan_status_approval');
            $table->dropIndex('idx_tagihan_jatuh_tempo');
            $table->dropIndex('idx_tagihan_tanggal');
        });
    }
};
